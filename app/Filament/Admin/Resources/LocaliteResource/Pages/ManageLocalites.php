<?php

namespace App\Filament\Admin\Resources\LocaliteResource\Pages;

use App\Filament\Admin\Resources\LocaliteResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLocalites extends ManageRecords
{
    protected static string $resource = LocaliteResource::class;

    protected function getHeaderActions(): array
    {
        return [static::getResource()::actionCreer()];
    }
}
