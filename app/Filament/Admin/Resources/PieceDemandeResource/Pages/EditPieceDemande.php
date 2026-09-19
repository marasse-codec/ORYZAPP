<?php

namespace App\Filament\Admin\Resources\PieceDemandeResource\Pages;

use App\Filament\Admin\Resources\PieceDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPieceDemande extends EditRecord
{
    protected static string $resource = PieceDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
