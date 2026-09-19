<?php

namespace App\Filament\Admin\Resources\TypeCultureResource\Pages;

use App\Filament\Admin\Resources\TypeCultureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeCultures extends ListRecords
{
    protected static string $resource = TypeCultureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
