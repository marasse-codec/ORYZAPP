<?php

namespace App\Http\Controllers\Suivi;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Services\ReglementService;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Suivi\Services\StatistiqueService;
use App\Domain\Suivi\Support\Graphique;
use App\Http\Controllers\Controller;
use App\Utils\Helper;
use Illuminate\Support\Facades\Auth;

/**
 * Tableau de bord : chaque rôle voit les chiffres de son étape du cycle de
 * vie, et seulement eux (le magasin, ses stocks ; la comptabilité, sa caisse).
 * Un utilisateur n'a qu'un rôle ; l'admin les voit tous.
 */
class DashboardController extends Controller
{
    public function __invoke(StatistiqueService $stats, ReglementService $reglements)
    {
        $user = Auth::user();
        $estAdmin = $user->hasRole(RoleEnum::admin->value);

        // Chaque bloc ne s'affiche qu'à qui en a l'usage :
        //  - le suivi des demandes, à qui consulte les demandes (pas le magasin ni la comptabilité) ;
        //  - la commission, le magasin et la caisse, à leurs rôles (l'admin voit tout).
        $voitDemandes = $user->can(PermissionEnum::voirDemandes->value);
        $voitCommission = $estAdmin || $user->hasRole(RoleEnum::agentCommission->value);
        $voitStock = $estAdmin || $user->hasRole(RoleEnum::gestionnaireStock->value);

        return view('dashboard', [
            'layout' => Helper::layout(),
            'estAdmin' => $estAdmin,
            'demandes' => $voitDemandes ? $this->suiviDesDemandes($stats, $user) : null,
            'commission' => $voitCommission ? $stats->commission() : null,
            'stock' => $voitStock ? $stats->stock() : null,
            // La caisse : à qui enregistre les règlements (comptable, admin).
            'reglements' => $user->can(PermissionEnum::gererReglements->value) ? [
                'synthese' => $reglements->synthese(),
                'acteurs' => $reglements->acteursEnImpaye(),
                'attributions' => $reglements->attributionsImpayees(),
            ] : null,
            // Le DP émet des demandes pour les acteurs : on lui en facilite l'accès.
            'peutCreer' => $user->can(PermissionEnum::creerDemande->value),
        ]);
    }

    /**
     * Le suivi des demandes du périmètre de l'utilisateur : statuts (« Reçu attribution »
     * ne compte que les attributions publiées), superficies, campagnes, dernières attributions.
     */
    private function suiviDesDemandes(StatistiqueService $stats, $user): array
    {
        $cas = collect(StatutDemandeE::cases());
        $statuts = $stats->demandesParStatut($user);
        $parCampagne = $stats->demandesParCampagne($user);

        return [
            'cas' => $cas,
            'statuts' => $statuts,
            // Le DP accède d'un clic à sa dernière demande de chaque statut.
            'dernieres' => $user->hasRole(RoleEnum::dp->value) ? $stats->derniereDemandeParStatut($user) : [],
            'graphiqueStatuts' => Graphique::anneau(
                $cas->map->libelle()->all(),
                $cas->map(fn (StatutDemandeE $s) => $statuts[$s->value])->all(),
                $cas->map->couleurGraphique()->all(),
            ),
            'graphiqueCampagnes' => $parCampagne->isEmpty() ? null : Graphique::barres(
                $parCampagne->keys()->all(),
                [['label' => 'Demandes', 'data' => $parCampagne->values()->all(), 'color' => '#1E5E3A']],
            ),
            'superficies' => $stats->superficies($user),
            'attributions' => $user->can(PermissionEnum::voirAttributions->value) ? $stats->dernieresAttributions() : null,
        ];
    }
}
