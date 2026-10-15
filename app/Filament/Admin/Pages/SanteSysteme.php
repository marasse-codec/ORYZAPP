<?php

namespace App\Filament\Admin\Pages;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Administration\Services\SanteSystemeService;
use Filament\Pages\Page;

/** L'écran « Santé du système » : un coup d'œil sur ce qui fonctionne et ce qui demande attention. */
class SanteSysteme extends Page
{
    protected static ?string $navigationGroup = 'Système';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Santé du système';

    protected static ?string $title = 'Santé du système';

    protected static ?string $slug = 'sante';

    protected static string $view = 'filament.admin.pages.sante-systeme';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermissionEnum::gererRessources->value) ?? false;
    }

    protected function getViewData(): array
    {
        return ['verifications' => app(SanteSystemeService::class)->verifier()];
    }
}
