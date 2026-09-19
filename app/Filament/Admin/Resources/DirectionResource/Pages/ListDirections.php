<?php

namespace App\Filament\Admin\Resources\DirectionResource\Pages;

use App\Filament\Admin\Resources\DirectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDirections extends ListRecords
{
    protected static string $resource = DirectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
