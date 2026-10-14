<?php

namespace App\Filament\Admin\Resources;

use App\Domain\Referentiel\Models\Service;
use App\Filament\Admin\Resources\ServiceResource\Pages;
use Filament\Forms;
use Filament\Tables;

class ServiceResource extends ReferentielResource
{
    protected static ?string $model = Service::class;

    protected static ?string $modelLabel = "service";

    protected static ?string $pluralModelLabel = "services";

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static function champs(): array
    {
        return [
            Forms\Components\TextInput::make('lib_service')->label('Libellé')->required()->maxLength(250),
        ];
    }

    protected static function colonnes(): array
    {
        return [
            Tables\Columns\TextColumn::make('lib_service')->label('Service')->searchable()->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageServices::route('/')];
    }
}
