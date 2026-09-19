<?php

namespace App\Filament\Admin\Resources\DepartementResource\Pages;

use App\Filament\Admin\Resources\DepartementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartement extends EditRecord
{
    protected static string $resource = DepartementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
