<?php

namespace App\Filament\Admin\Resources\TypeMouvementStockResource\Pages;

use App\Filament\Admin\Resources\TypeMouvementStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTypeMouvementStock extends EditRecord
{
    protected static string $resource = TypeMouvementStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
