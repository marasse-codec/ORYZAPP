<?php

namespace App\Filament\Admin\Resources\RetraitResource\Pages;

use App\Filament\Admin\Resources\RetraitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRetrait extends EditRecord
{
    protected static string $resource = RetraitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
