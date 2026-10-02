<?php

namespace App\Filament\Admin\Resources\ActeurResource\Pages;

use App\Filament\Admin\Resources\ActeurResource;
use App\Livewire\StatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListActeurs extends ListRecords
{
    protected static string $resource = ActeurResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }

    #[Override]
    protected function getTableFilters(): array
    {
        return [];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau demandeur')
                ->icon('heroicon-o-user-plus')
        ];
    }

}
