<?php

namespace App\Filament\Admin\Resources\PieceDemandeResource\Pages;

use App\Filament\Admin\Resources\PieceDemandeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPieceDemandes extends ListRecords
{
    protected static string $resource = PieceDemandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
