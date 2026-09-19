<?php

namespace App\Filament\Admin\Resources\JournalActionResource\Pages;

use App\Filament\Admin\Resources\JournalActionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalAction extends CreateRecord
{
    protected static string $resource = JournalActionResource::class;
}
