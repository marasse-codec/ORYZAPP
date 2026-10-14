<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\TypeActeur;
use App\Filament\Admin\Resources\TypeActeurResource\Pages;
use Filament\Forms;
use Filament\Tables;

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
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('code_type_acteur')->label('Code')->sortable(),
            Tables\Columns\TextColumn::make('lib_type_acteur')->label("Type d'acteur")->searchable()->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTypesActeurs::route('/')];
    }
}
