<?php

namespace App\Filament\Admin\Resources\StatutAttributionResource\Pages;

use App\Filament\Admin\Resources\StatutAttributionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatutAttribution extends EditRecord
{
    protected static string $resource = StatutAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
