<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\Departement;
use App\Filament\Admin\Resources\DepartementResource\Pages;
use Filament\Forms;
use Filament\Tables;

class DepartementResource extends ReferentielResource
{
    protected static ?string $model = Departement::class;

    protected static ?string $modelLabel = "département";

    protected static ?string $pluralModelLabel = "départements";

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_departement')->label('Libellé')->required()->maxLength(250),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_departement')->label('Département')->searchable()->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDepartements::route('/')];
    }
}
