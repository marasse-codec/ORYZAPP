<?php

namespace App\Filament\Admin\Resources\CultureResource\Pages;

use App\Filament\Admin\Resources\CultureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCultures extends ListRecords
{
    protected static string $resource = CultureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
