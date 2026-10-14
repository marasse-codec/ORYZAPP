<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\Campagne;
use App\Filament\Admin\Resources\CampagneResource\Pages;
use Filament\Forms;
use Filament\Tables;

class CampagneResource extends ReferentielResource
{
    protected static ?string $model = Campagne::class;

    protected static ?string $modelLabel = "campagne";

    protected static ?string $pluralModelLabel = "campagnes";

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_campagne')->label('Libellé')->required()->maxLength(30),
            Forms\Components\DatePicker::make('mois_debut_campagne')->label('Début')->required(),
            Forms\Components\DatePicker::make('mois_fin_campagne')->label('Fin')->required()->afterOrEqual('mois_debut_campagne'),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_campagne')->label('Libellé')->searchable(),
            Tables\Columns\TextColumn::make('mois_debut_campagne')->label('Début')->date('m/Y')->sortable(),
            Tables\Columns\TextColumn::make('mois_fin_campagne')->label('Fin')->date('m/Y')->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageCampagnes::route('/')];
    }
}
