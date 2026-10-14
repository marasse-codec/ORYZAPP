<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\TypePiece;
use App\Filament\Admin\Resources\TypePieceResource\Pages;
use Filament\Forms;
use Filament\Tables;

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
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_type_piece')->label('Type de pièce')->searchable()->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTypesPieces::route('/')];
    }
}
