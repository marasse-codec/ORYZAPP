<?php

namespace App\Filament\Admin\Resources\CopieDemandeResource\Pages;

use App\Filament\Admin\Resources\CopieDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCopieDemande extends EditRecord
{
    protected static string $resource = CopieDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
