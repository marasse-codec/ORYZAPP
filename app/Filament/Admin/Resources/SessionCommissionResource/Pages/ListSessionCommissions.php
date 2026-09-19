<?php

namespace App\Filament\Admin\Resources\SessionCommissionResource\Pages;

use App\Filament\Admin\Resources\SessionCommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSessionCommissions extends ListRecords
{
    protected static string $resource = SessionCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
