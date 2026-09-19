<?php

namespace App\Filament\Admin\Resources\BeneficiairesTypeCultureResource\Pages;

use App\Filament\Admin\Resources\BeneficiairesTypeCultureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBeneficiairesTypeCultures extends ListRecords
{
    protected static string $resource = BeneficiairesTypeCultureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
