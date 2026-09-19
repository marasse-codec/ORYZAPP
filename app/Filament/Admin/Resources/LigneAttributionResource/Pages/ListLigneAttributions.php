<?php

namespace App\Filament\Admin\Resources\LigneAttributionResource\Pages;

use App\Filament\Admin\Resources\LigneAttributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLigneAttributions extends ListRecords
{
    protected static string $resource = LigneAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
