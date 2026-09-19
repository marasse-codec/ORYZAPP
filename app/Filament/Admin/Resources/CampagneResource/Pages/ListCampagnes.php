<?php

namespace App\Filament\Admin\Resources\CampagneResource\Pages;

use App\Filament\Admin\Resources\CampagneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampagnes extends ListRecords
{
    protected static string $resource = CampagneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
