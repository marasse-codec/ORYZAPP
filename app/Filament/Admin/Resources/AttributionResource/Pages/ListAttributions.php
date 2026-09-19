<?php

namespace App\Filament\Admin\Resources\AttributionResource\Pages;

use App\Filament\Admin\Resources\AttributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttributions extends ListRecords
{
    protected static string $resource = AttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
