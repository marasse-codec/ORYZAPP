<?php

namespace App\Filament\Admin\Resources\AppuiResource\Pages;

use App\Filament\Admin\Resources\AppuiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppui extends EditRecord
{
    protected static string $resource = AppuiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
