<?php

namespace App\Filament\Admin\Resources\TypeAppuiResource\Pages;

use App\Filament\Admin\Resources\TypeAppuiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeAppuis extends ListRecords
{
    protected static string $resource = TypeAppuiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
