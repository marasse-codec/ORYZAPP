<?php

namespace App\Filament\Admin\Resources\DepartementResource\Pages;

use App\Filament\Admin\Resources\DepartementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartements extends ListRecords
{
    protected static string $resource = DepartementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
