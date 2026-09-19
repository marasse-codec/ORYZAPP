<?php

namespace App\Filament\Admin\Resources\CommissionAttributionResource\Pages;

use App\Filament\Admin\Resources\CommissionAttributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommissionAttributions extends ListRecords
{
    protected static string $resource = CommissionAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
