<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\TypeSystemeProduction;
use App\Filament\Admin\Resources\TypeSystemeProductionResource\Pages;
use Filament\Forms;
use Filament\Tables;

class TypeSystemeProductionResource extends ReferentielResource
{
    protected static ?string $model = TypeSystemeProduction::class;

    protected static ?string $modelLabel = "système de production";

    protected static ?string $pluralModelLabel = "systèmes de production";

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_type_systeme_production')->label('Libellé')->required()->maxLength(60),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_type_systeme_production')->label('Système de production')->searchable()->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTypesSystemesProductions::route('/')];
    }
}
