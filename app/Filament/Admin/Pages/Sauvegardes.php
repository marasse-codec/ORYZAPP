<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Shared\Services\LanceurArtisan;
use App\Domain\Suivi\Services\SauvegardeService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** Les sauvegardes : l'admin voit leur santé, télécharge une archive, ou en lance une à la demande. */
class Sauvegardes extends Page
{
    protected static ?string $navigationGroup = 'Système';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Sauvegardes';

    protected static ?string $title = 'Sauvegardes';

    protected static ?string $slug = 'sauvegardes';

    protected static string $view = 'filament.admin.pages.sauvegardes';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('lancer')
                ->label('Lancer une sauvegarde')
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->modalDescription('La sauvegarde (base de données et pièces téléversées) s\'exécute en arrière-plan : actualisez la page dans quelques instants.')
                ->action(function () {
                    app(LanceurArtisan::class)->lancer('backup:run');

                    Notification::make()->title('Sauvegarde lancée')->body('Elle apparaîtra dans la liste à sa fin.')->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $sauvegardes = app(SauvegardeService::class);

        return [
            'etat' => $sauvegardes->etat(),
            'archives' => $sauvegardes->archives(),
        ];
    }
}
