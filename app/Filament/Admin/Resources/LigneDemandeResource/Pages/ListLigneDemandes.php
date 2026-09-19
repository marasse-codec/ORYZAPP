<?php

namespace App\Filament\Admin\Resources\LigneDemandeResource\Pages;

use App\Filament\Admin\Resources\LigneDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLigneDemandes extends ListRecords
{
    protected static string $resource = LigneDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
