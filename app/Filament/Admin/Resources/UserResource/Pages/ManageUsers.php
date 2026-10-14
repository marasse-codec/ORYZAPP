<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Domain\Acces\Services\CompteService;
use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->using(fn (array $data) => app(CompteService::class)->creer($data)),
        ];
    }
}
