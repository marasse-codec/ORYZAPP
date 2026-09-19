<?php

namespace App\Filament\Admin\Resources\LigneAttributionResource\Pages;

use App\Filament\Admin\Resources\LigneAttributionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLigneAttribution extends EditRecord
{
    protected static string $resource = LigneAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
