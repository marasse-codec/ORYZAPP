<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\Localite;
use App\Filament\Admin\Resources\LocaliteResource\Pages;
use Filament\Forms;
use Filament\Tables;

class LocaliteResource extends ReferentielResource
{
    protected static ?string $model = Localite::class;

    protected static ?string $modelLabel = "localité";

    protected static ?string $pluralModelLabel = "localités";

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_localite')->label('Libellé')->required()->maxLength(30),
            Forms\Components\Select::make('id_departement')->label('Département')->relationship('departement', 'lib_departement')->searchable()->preload()->required(),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_localite')->label('Localité')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('departement.lib_departement')->label('Département')->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLocalites::route('/')];
    }
}
