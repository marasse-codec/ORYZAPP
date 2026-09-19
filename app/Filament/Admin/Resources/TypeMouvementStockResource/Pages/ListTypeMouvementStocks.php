<?php

namespace App\Filament\Admin\Resources\TypeMouvementStockResource\Pages;

use App\Filament\Admin\Resources\TypeMouvementStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeMouvementStocks extends ListRecords
{
    protected static string $resource = TypeMouvementStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
