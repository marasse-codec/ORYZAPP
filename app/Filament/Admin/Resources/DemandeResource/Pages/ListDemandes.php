<?php

namespace App\Filament\Admin\Resources\DemandeResource\Pages;

use App\Filament\Admin\Resources\DemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDemandes extends ListRecords
{
    protected static string $resource = DemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
