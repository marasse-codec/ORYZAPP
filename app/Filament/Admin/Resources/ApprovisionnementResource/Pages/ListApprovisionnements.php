<?php

namespace App\Filament\Admin\Resources\ApprovisionnementResource\Pages;

use App\Filament\Admin\Resources\ApprovisionnementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApprovisionnements extends ListRecords
{
    protected static string $resource = ApprovisionnementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
