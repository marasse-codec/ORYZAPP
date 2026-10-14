<?php

namespace App\Filament\Admin\Resources\CampagneResource\Pages;

use App\Filament\Admin\Resources\CampagneResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCampagnes extends ManageRecords
{
    protected static string $resource = CampagneResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
