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
 * vie. Un utilisateur n'a qu'un rôle ; l'admin les voit tous.
 */
class DashboardController extends Controller
{
    public function __invoke(StatistiqueService $stats, ReglementService $reglements)
    {
        $user = Auth::user();
        $estAdmin = $user->hasRole(RoleEnum::admin->value);

        // Blocs affichés : un par rôle, tous pour l'admin.
        $voitCommission = $estAdmin || $user->hasRole(RoleEnum::agentCommission->value);
        $voitStock = $estAdmin || $user->hasRole(RoleEnum::gestionnaireStock->value);

        $parCampagne = $stats->demandesParCampagne($user);

        //$statuts : tableau avec en clé les valeurs de l'enum StatutDemande
        $statuts = $stats->demandesParStatut($user);
        $cas = collect(StatutDemandeE::cases());

        // Le DP accède d'un clic à sa dernière demande de chaque statut.
        $dernieres = $user->hasRole(RoleEnum::dp->value) ? $stats->derniereDemandeParStatut($user) : [];

        return view('dashboard', [
            'layout' => Helper::layout(),
            'estAdmin' => $estAdmin,
            'cas' => $cas,
            'statuts' => $statuts,
            'dernieres' => $dernieres,
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
            'attributions' => $stats->dernieresAttributions(),
            'commission' => $voitCommission ? $stats->commission() : null,
            'stock' => $voitStock ? $stats->stock() : null,
            // Les impayés : le suivi de la caisse revient à qui peut enregistrer les règlements (l'admin).
            'reglements' => $user->can(PermissionEnum::gererReglements->value) ? [
                'synthese' => $reglements->synthese(),
                'acteurs' => $reglements->acteursEnImpaye(),
                'attributions' => $reglements->attributionsImpayees(),
            ] : null,
            // Le DP émet des demandes pour les acteurs : on lui en facilite l'accès.
            'peutCreer' => $user->can(PermissionEnum::creerDemande->value),
        ]);
    }
}
