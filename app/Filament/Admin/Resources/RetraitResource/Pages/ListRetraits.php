<?php

namespace App\Filament\Admin\Resources\RetraitResource\Pages;

use App\Filament\Admin\Resources\RetraitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRetraits extends ListRecords
{
    protected static string $resource = RetraitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
