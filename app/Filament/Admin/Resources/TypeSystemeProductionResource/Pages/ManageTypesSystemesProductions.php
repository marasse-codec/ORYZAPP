<?php

namespace App\Filament\Admin\Resources\TypeSystemeProductionResource\Pages;

use App\Filament\Admin\Resources\TypeSystemeProductionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTypesSystemesProductions extends ManageRecords
{
    protected static string $resource = TypeSystemeProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [static::getResource()::actionCreer()];
    }
}
