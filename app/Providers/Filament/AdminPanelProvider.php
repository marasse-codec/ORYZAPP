<?php

namespace App\Providers\Filament;

use App\Domain\Acces\Enums\PermissionEnum;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $vertOryza = [
            50 => '238, 247, 241',
            100 => '213, 236, 221',
            200 => '174, 216, 191',
            300 => '125, 189, 152',
            400 => '79, 157, 114',
            500 => '47, 128, 85',
            600 => '30, 94, 58',
            700 => '26, 76, 49',
            800 => '22, 63, 41',
            900 => '18, 52, 34',
            950 => '11, 36, 22',
        ];

        return $panel
            ->id('admin')
            ->path('administration')
            ->brandName('Administration')
            ->navigationGroups(['Référentiels', 'Accès', 'Système'])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()->label('Retour à l\'application')->url(fn () => route('dashboard'))->icon('heroicon-o-arrow-left'),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            // ->pages([
            //     Pages\Dashboard::class,
            // ])
            // --- CHARTE GRAPHIQUE ORYZA (resources/assets/06_Charte) ---
            ->brandLogo(asset('images/brand/ORYZA_logo_sans_slogan_couleur.svg'))
            ->darkModeBrandLogo(asset('images/brand/ORYZA_logo_sans_slogan_negatif.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.svg'))
            ->font('Poppins')
            ->colors([
                // Vert ORYZA #1E5E3A porté par la nuance 600, celle des boutons Filament.
                'primary' => $vertOryza,
                'gray'    => Color::Stone,

                'danger'  => Color::Rose,
                'info'    => Color::Sky,
                'success' => $vertOryza,
                // Or riz #E5A93B
                'warning' => Color::hex('#E5A93B'),
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                'auth',
                'permission:'.PermissionEnum::gererRessources->value,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
