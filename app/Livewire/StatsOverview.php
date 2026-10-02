<?php

namespace App\Livewire;

use App\Models\Acteur;
use App\Models\Demande;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Acteurs', Acteur::count())
                ->description('Acteurs enregistrés')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Demandes en attente', Demande::attribuables(auth()->user())->count())
                ->description('En cours d\'instruction')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}