<?php

namespace App\Filament\Admin\Resources\ActeurResource\Pages;

use App\Filament\Admin\Resources\ActeurResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActeur extends EditRecord
{
    protected static string $resource = ActeurResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
