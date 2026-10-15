<?php

namespace App\Providers;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Administration\Services\ParametreService;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Policies\AttributionPolicy;
use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Listeners\JournaliserConnexions;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Stock\Models\Approvisionnement;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Observers\AppuiObserver;
use App\Domain\Stock\Policies\AppuiPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Les paramètres lus une fois sont gardés le temps de la requête.
        $this->app->scoped(ParametreService::class);
    }

    public function boot(): void
    {
        // Le code est rangé par contexte (app/Domain) : ni la base ni les factories
        // ne doivent dépendre de cet emplacement.
        //
        // Les types polymorphes se stockent par alias, pas par nom de classe : on peut
        // déplacer une classe sans réécrire les données (journal, abonnements).
        Relation::morphMap([
            'demande' => Demande::class,
            'attribution' => Attribution::class,
            'session_commission' => SessionCommission::class,
            'approvisionnement' => Approvisionnement::class,
            'equipement' => Equipement::class,
            'appui' => Appui::class,
            // Ajouts v2 : cibles possibles du journal.
            'user' => User::class,
            'campagne' => Campagne::class,
            'role' => Role::class,
        ]);

        // Une factory se trouve par son nom (« DemandeFactory »), où que soit le modèle.
        Factory::guessFactoryNamesUsing(fn (string $modele) => 'Database\\Factories\\'.class_basename($modele).'Factory');

        // Les politiques ne sont plus découvertes par convention (le modèle n'est plus dans App\Models).
        Gate::policy(Appui::class, AppuiPolicy::class);
        Gate::policy(Attribution::class, AttributionPolicy::class);

        Appui::observe(AppuiObserver::class);

        // Connexions, échecs et déconnexions sont notés au journal.
        Event::subscribe(JournaliserConnexions::class);

        // La politique de mot de passe vient des paramètres réglés par l'administrateur.
        // Elle est lue au moment de valider, pas au démarrage (la base peut ne pas exister encore).
        Password::defaults(fn () => app(ParametreService::class)->reglePassword());

        // L'administrateur passe toutes les autorisations sans qu'on ait à lui
        // rattacher chaque permission une à une.
        Gate::before(fn ($user) => $user->hasRole(RoleEnum::admin->value) ? true : null);

        // Saisir ou modifier une demande : cf. RoleEnum::peutSaisirDesDemandes.
        Gate::define('saisir-demandes', fn (User $user) => $user->roleEnum()?->peutSaisirDesDemandes() ?? false);

        Gate::define('access-mouvement-usecases', function (User $user) {
            return $user->hasRole([RoleEnum::admin->value, RoleEnum::gestionnaireStock->value]);
        });
    }
}
