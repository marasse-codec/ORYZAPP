<?php

namespace App\Filament\Admin\Resources\TypeActeurResource\Pages;

use App\Filament\Admin\Resources\TypeActeurResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTypeActeur extends EditRecord
{
    protected static string $resource = TypeActeurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
