<?php

namespace App\Filament\Admin\Resources\TypePieceResource\Pages;

use App\Filament\Admin\Resources\TypePieceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTypePiece extends EditRecord
{
    protected static string $resource = TypePieceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
