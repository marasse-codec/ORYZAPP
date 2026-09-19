<?php

namespace App\Filament\Admin\Resources\MouvementStockResource\Pages;

use App\Filament\Admin\Resources\MouvementStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMouvementStock extends EditRecord
{
    protected static string $resource = MouvementStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
