<?php

namespace App\Filament\Admin\Resources\TypeActeurResource\Pages;

use App\Filament\Admin\Resources\TypeActeurResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTypesActeurs extends ManageRecords
{
    protected static string $resource = TypeActeurResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
