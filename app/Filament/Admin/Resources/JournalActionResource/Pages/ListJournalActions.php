<?php

namespace App\Filament\Admin\Resources\JournalActionResource\Pages;

use App\Filament\Admin\Resources\JournalActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalActions extends ListRecords
{
    protected static string $resource = JournalActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
