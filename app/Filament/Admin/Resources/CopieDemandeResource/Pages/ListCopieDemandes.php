<?php

namespace App\Filament\Admin\Resources\CopieDemandeResource\Pages;

use App\Filament\Admin\Resources\CopieDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCopieDemandes extends ListRecords
{
    protected static string $resource = CopieDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
