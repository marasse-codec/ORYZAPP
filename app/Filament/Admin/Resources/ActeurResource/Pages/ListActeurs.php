<?php

namespace App\Filament\Admin\Resources\ActeurResource\Pages;

use App\Filament\Admin\Resources\ActeurResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActeurs extends ListRecords
{
    protected static string $resource = ActeurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
