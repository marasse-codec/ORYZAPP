<?php

namespace App\Filament\Admin\Resources\StatutDemandeResource\Pages;

use App\Filament\Admin\Resources\StatutDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatutDemandes extends ListRecords
{
    protected static string $resource = StatutDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
