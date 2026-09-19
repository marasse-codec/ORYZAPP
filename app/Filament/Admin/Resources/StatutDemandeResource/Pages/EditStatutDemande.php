<?php

namespace App\Filament\Admin\Resources\StatutDemandeResource\Pages;

use App\Filament\Admin\Resources\StatutDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatutDemande extends EditRecord
{
    protected static string $resource = StatutDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
