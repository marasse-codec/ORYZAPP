<?php

namespace App\Filament\Admin\Resources\MouvementStockResource\Pages;

use App\Filament\Admin\Resources\MouvementStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMouvementStocks extends ListRecords
{
    protected static string $resource = MouvementStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
