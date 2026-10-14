<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\TypeAppui;
use App\Filament\Admin\Resources\TypeAppuiResource\Pages;
use Filament\Forms;
use Filament\Tables;

class TypeAppuiResource extends ReferentielResource
{
    protected static ?string $model = TypeAppui::class;

    protected static ?string $modelLabel = "type d'appui";

    protected static ?string $pluralModelLabel = "types d'appuis";

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_type_appui')->label('Libellé')->required()->maxLength(100),
            Forms\Components\Select::make('id_service')->label('Service fournisseur')->relationship('service', 'lib_service')->searchable()->preload()->required(),
            Forms\Components\Toggle::make('est_intrant')->label("Intrant (consommable), sinon matériel suivi à l'équipement"),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_type_appui')->label("Type d'appui")->searchable()->sortable(),
            Tables\Columns\TextColumn::make('service.lib_service')->label('Service')->sortable(),
            Tables\Columns\IconColumn::make('est_intrant')->label('Intrant')->boolean(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTypesAppuis::route('/')];
    }
}
