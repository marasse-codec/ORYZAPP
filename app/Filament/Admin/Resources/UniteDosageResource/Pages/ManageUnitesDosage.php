<?php

namespace App\Filament\Admin\Resources\UniteDosageResource\Pages;

use App\Filament\Admin\Resources\UniteDosageResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUnitesDosage extends ManageRecords
{
    protected static string $resource = UniteDosageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
