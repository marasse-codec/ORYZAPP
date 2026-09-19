<?php

namespace App\Filament\Admin\Resources\TraitementResource\Pages;

use App\Filament\Admin\Resources\TraitementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTraitement extends EditRecord
{
    protected static string $resource = TraitementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
