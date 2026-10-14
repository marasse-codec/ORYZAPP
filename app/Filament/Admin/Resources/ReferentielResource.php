<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Shared\Contracts\EstUtilisable;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Socle des référentiels (types, localités, campagnes…) : des listes que
 * l'administration tient à la main. Chaque référentiel dit seulement quels
 * champs et quelles colonnes il porte ; le reste est commun :
 *
 *  - réservé à l'administration ;
 *  - création et modification dans une fenêtre, sans page dédiée ;
 *  - suppression refusée, avec un message, quand l'élément est utilisé.
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
                Tables\Actions\EditAction::make(),
                static::actionSupprimer(),
            ])
            ->paginated([25, 50, 100]);
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
                return (bool) $record->delete();
            } catch (QueryException) {
                return false;
            }
        });
    }
}
