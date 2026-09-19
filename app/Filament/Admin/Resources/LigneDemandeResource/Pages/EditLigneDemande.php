<?php

namespace App\Filament\Admin\Resources\LigneDemandeResource\Pages;

use App\Filament\Admin\Resources\LigneDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLigneDemande extends EditRecord
{
    protected static string $resource = LigneDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
