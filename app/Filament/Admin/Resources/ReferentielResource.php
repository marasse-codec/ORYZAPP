<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Journal\Support\Ecarts;
use App\Domain\Shared\Contracts\EstUtilisable;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Socle des référentiels (types, localités, campagnes…) : des listes que
 * l'administration tient à la main. Chaque référentiel dit seulement quels
 * champs et quelles colonnes il porte ; le reste est commun :
 *
 *  - réservé à l'administration ;
 *  - création et modification dans une fenêtre, sans page dédiée ;
 *  - suppression refusée, avec un message, quand l'élément est utilisé ;
 *  - chaque création, modification et suppression est notée au journal
 *    (valeurs avant/après) : un prix ou une dose changés restent retrouvables ;
 *  - un référentiel peut aussi porter des rattachements (tables de liaison,
 *    ex. les pièces exigées d'un profil) : il les déclare avec
 *    champsRattachements(), rattachementsActuels() et enregistrerRattachements().
 *
 * Seules des données de référence passent par ici. Les données transactionnelles
 * (demandes, attributions, stocks…) ne se modifient que par les cas d'usage du
 * métier, qui tiennent les règles et la transaction.
 */
abstract class ReferentielResource extends Resource
{
    protected static ?string $navigationGroup = 'Référentiels';

    /** @return array<int, \Filament\Forms\Components\Component> */
    abstract protected static function champs(): array;

    /** @return array<int, \Filament\Tables\Columns\Column> */
    abstract protected static function colonnes(): array;

    /**
     * Les champs du formulaire qui ne sont pas des colonnes de l'élément mais
     * des rattachements (lignes d'une table de liaison). Aucun par défaut.
     *
     * @return list<string>
     */
    protected static function champsRattachements(): array
    {
        return [];
    }

    /**
     * Les rattachements actuels, pour pré-remplir la fenêtre de modification.
     *
     * @return array<string, mixed>
     */
    protected static function rattachementsActuels(Model $record): array
    {
        return [];
    }

    /**
     * Enregistre les rattachements saisis et renvoie ce qui a changé, à noter au journal.
     *
     * @param  array<string, mixed>  $rattachements
     * @return array<string, array{avant: mixed, apres: mixed}>
     */
    protected static function enregistrerRattachements(Model $record, array $rattachements): array
    {
        return [];
    }

    /** Bouton « Nouveau » des pages de référentiel : crée l'élément, ses rattachements, et le note au journal. */
    public static function actionCreer(): Actions\CreateAction
    {
        return Actions\CreateAction::make()->using(fn (array $data) => static::creer($data));
    }

    /** @param  array<string, mixed>  $data  Les valeurs du formulaire. */
    public static function creer(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $record = static::getModel()::create(Arr::except($data, static::champsRattachements()));
            $rattachements = static::enregistrerRattachements($record, Arr::only($data, static::champsRattachements()));

            JournalAction::consigner(ActionJournalEnum::referentielCree, $record, [
                'element' => static::getModelLabel(),
            ] + $record->attributesToArray() + array_map(fn ($ecart) => $ecart['apres'], $rattachements));

            return $record;
        });
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(PermissionEnum::gererRessources->value) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::champs());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::colonnes())
            ->actions([
                static::actionModifier(),
                static::actionSupprimer(),
            ])
            ->paginated([25, 50, 100]);
    }

    /** Modifie l'élément et note au journal ce qui a changé (avant → après). */
    protected static function actionModifier(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->mutateRecordDataUsing(fn (array $data, Model $record) => $data + static::rattachementsActuels($record))
            ->using(function (Model $record, array $data) {
                return DB::transaction(function () use ($record, $data) {
                    $record->fill(Arr::except($data, static::champsRattachements()));
                    $ecarts = Ecarts::duModele($record);
                    $record->save();
                    $ecarts += static::enregistrerRattachements($record, Arr::only($data, static::champsRattachements()));

                    if ($ecarts !== []) {
                        JournalAction::consigner(ActionJournalEnum::referentielModifie, $record, ['element' => static::getModelLabel()] + $ecarts);
                    }

                    return $record;
                });
            });
    }

    /**
     * Supprime, sauf si l'élément est utilisé : la base refuserait parfois (clé
     * étrangère), parfois non (suppression en cascade) ; la règle du modèle
     * décide dans tous les cas.
     */
    protected static function actionSupprimer(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()->using(function (Model $record, Tables\Actions\DeleteAction $action) {
            $action->failureNotificationTitle('Suppression impossible : cet élément est utilisé ailleurs.');

            if ($record instanceof EstUtilisable && $record->estUtilise()) {
                return false;
            }

            try {
                return DB::transaction(function () use ($record) {
                    // La trace garde tout le contenu de l'élément : il reste lisible après sa disparition.
                    JournalAction::consigner(ActionJournalEnum::referentielSupprime, $record, ['element' => static::getModelLabel()] + $record->attributesToArray());

                    return (bool) $record->delete();
                });
            } catch (QueryException) {
                return false;
            }
        });
    }

    /** Lance une opération du métier et en affiche le résultat : succès, ou la règle qui l'a refusée. */
    protected static function executer(callable $operation, string $succes): void
    {
        try {
            $operation();
            Notification::make()->title($succes)->success()->send();
        } catch (RegleMetierViolee $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
