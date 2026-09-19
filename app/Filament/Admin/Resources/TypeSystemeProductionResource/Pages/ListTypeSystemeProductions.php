<?php

namespace App\Filament\Admin\Resources\TypeSystemeProductionResource\Pages;

use App\Filament\Admin\Resources\TypeSystemeProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeSystemeProductions extends ListRecords
{
    protected static string $resource = TypeSystemeProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
