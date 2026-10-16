<?php

namespace App\Filament\Admin\Resources\TypePieceResource\Pages;

use App\Filament\Admin\Resources\TypePieceResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTypesPieces extends ManageRecords
{
    protected static string $resource = TypePieceResource::class;

    protected function getHeaderActions(): array
    {
        return [static::getResource()::actionCreer()];
    }
}
