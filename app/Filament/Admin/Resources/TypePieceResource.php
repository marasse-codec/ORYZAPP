<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypePiece;
use App\Domain\Referentiel\Services\ExigencePieceService;
use App\Filament\Admin\Resources\TypePieceResource\Pages;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

/**
 * Les pièces que l'on peut demander aux demandeurs (scan de la demande écrite,
 * agrément, liste des membres…) et, pour chacune, les profils qui doivent la
 * fournir. Ajouter une pièce ici la fait apparaître dans le formulaire de
 * demande et sur la page vitrine des profils cochés.
 */
class TypePieceResource extends ReferentielResource
{
    protected static ?string $model = TypePiece::class;

    protected static ?string $modelLabel = "type de pièce";

    protected static ?string $pluralModelLabel = "types de pièces";

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_type_piece')->label('Libellé')->required()->maxLength(60),
            Forms\Components\Repeater::make('profils')
                ->label('Profils qui doivent la fournir')
                ->helperText('Sans profil, la pièce n\'est demandée à personne.')
                ->schema([
                    Forms\Components\Select::make('id_type_acteur')
                        ->label("Type d'acteur")
                        ->options(fn () => TypeActeur::orderBy('lib_type_acteur')->pluck('lib_type_acteur', 'id_type_acteur'))
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->required(),
                    Forms\Components\Toggle::make('est_obligatoire')->label('Obligatoire')->default(true)->inline(false),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->reorderable(false)
                ->columnSpanFull()
                ->addActionLabel('Ajouter un profil'),
        ];
    }

    protected static function champsRattachements(): array
    {
        return ['profils'];
    }

    protected static function rattachementsActuels(Model $record): array
    {
        return ['profils' => app(ExigencePieceService::class)->profilsDeLaPiece($record)];
    }

    protected static function enregistrerRattachements(Model $record, array $rattachements): array
    {
        return app(ExigencePieceService::class)->fixerProfilsDeLaPiece($record, $rattachements['profils'] ?? []);
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_type_piece')->label('Type de pièce')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('typesActeurs.lib_type_acteur')->label('Exigée de')->badge()->placeholder('Personne'),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTypesPieces::route('/')];
    }
}
