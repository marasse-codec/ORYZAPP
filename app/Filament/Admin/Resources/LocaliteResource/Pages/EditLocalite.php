<?php

namespace App\Filament\Admin\Resources\LocaliteResource\Pages;

use App\Filament\Admin\Resources\LocaliteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocalite extends EditRecord
{
    protected static string $resource = LocaliteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
