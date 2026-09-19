<?php

namespace App\Filament\Admin\Resources\DemandeCorrectionResource\Pages;

use App\Filament\Admin\Resources\DemandeCorrectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDemandeCorrections extends ListRecords
{
    protected static string $resource = DemandeCorrectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
