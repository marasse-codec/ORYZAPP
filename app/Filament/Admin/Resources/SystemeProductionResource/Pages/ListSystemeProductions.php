<?php

namespace App\Filament\Admin\Resources\SystemeProductionResource\Pages;

use App\Filament\Admin\Resources\SystemeProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemeProductions extends ListRecords
{
    protected static string $resource = SystemeProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
