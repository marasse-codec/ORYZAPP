<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\UniteDosage;
use App\Filament\Admin\Resources\UniteDosageResource\Pages;
use Filament\Forms;
use Filament\Tables;

class UniteDosageResource extends ReferentielResource
{
    protected static ?string $model = UniteDosage::class;

    protected static ?string $modelLabel = "unité de dosage";

    protected static ?string $pluralModelLabel = "unités de dosage";

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('code_unite')->label('Code')->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('lib_unite')->label('Libellé')->required()->maxLength(255),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('code_unite')->label('Code')->sortable(),
            Tables\Columns\TextColumn::make('lib_unite')->label('Unité')->searchable()->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUnitesDosage::route('/')];
    }
}
