<?php

namespace App\Filament\Admin\Resources\DepartementResource\Pages;

use App\Filament\Admin\Resources\DepartementResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDepartements extends ManageRecords
{
    protected static string $resource = DepartementResource::class;

    protected function getHeaderActions(): array
    {
        return [static::getResource()::actionCreer()];
    }
}
