<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypePiece;
use App\Domain\Referentiel\Services\ExigencePieceService;
use App\Filament\Admin\Resources\TypeActeurResource\Pages;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

/**
 * Les profils de demandeurs (producteur individuel, coopérative…) et les
 * pièces que chacun doit joindre à sa demande.
 */
class TypeActeurResource extends ReferentielResource
{
    protected static ?string $model = TypeActeur::class;

    protected static ?string $modelLabel = "type d'acteur";

    protected static ?string $pluralModelLabel = "types d'acteurs";

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('code_type_acteur')->label('Code')->required()->maxLength(3),
            Forms\Components\TextInput::make('lib_type_acteur')->label('Libellé')->required()->maxLength(60),
            Forms\Components\Repeater::make('pieces')
                ->label('Pièces à fournir')
                ->schema([
                    Forms\Components\Select::make('id_type_piece')
                        ->label('Pièce')
                        ->options(fn () => TypePiece::orderBy('lib_type_piece')->pluck('lib_type_piece', 'id_type_piece'))
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->required(),
                    Forms\Components\Toggle::make('est_obligatoire')->label('Obligatoire')->default(true)->inline(false),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->reorderable(false)
                ->columnSpanFull()
                ->addActionLabel('Ajouter une pièce'),
        ];
    }

    protected static function champsRattachements(): array
    {
        return ['pieces'];
    }

    protected static function rattachementsActuels(Model $record): array
    {
        return ['pieces' => app(ExigencePieceService::class)->piecesDuProfil($record)];
    }

    protected static function enregistrerRattachements(Model $record, array $rattachements): array
    {
        return app(ExigencePieceService::class)->fixerPiecesDuProfil($record, $rattachements['pieces'] ?? []);
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('code_type_acteur')->label('Code')->sortable(),
            Tables\Columns\TextColumn::make('lib_type_acteur')->label("Type d'acteur")->searchable()->sortable(),
            Tables\Columns\TextColumn::make('types_pieces_count')->counts('typesPieces')->label('Pièces à fournir'),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTypesActeurs::route('/')];
    }
}
