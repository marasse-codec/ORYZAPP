<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\Appui;
use App\Models\Attribution;
use App\Observers\AppuiObserver;
use App\Observers\AttributionObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Appui::observe(AppuiObserver::class);
        Attribution::observe(AttributionObserver::class);
        // L'administrateur passe toutes les autorisations sans qu'on ait à lui
        // rattacher chaque permission une à une.
        Gate::before(fn ($user) => $user->hasRole(RoleEnum::admin->value) ? true : null);
    }
}
