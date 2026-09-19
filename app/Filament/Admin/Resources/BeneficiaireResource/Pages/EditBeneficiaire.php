<?php

namespace App\Filament\Admin\Resources\BeneficiaireResource\Pages;

use App\Filament\Admin\Resources\BeneficiaireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBeneficiaire extends EditRecord
{
    protected static string $resource = BeneficiaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
