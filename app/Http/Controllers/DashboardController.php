<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Services\StatistiqueService;
use App\Utils\Helper;
use Illuminate\Support\Facades\Auth;

/**
 * Tableau de bord : chaque rôle voit les chiffres de son étape du cycle de
 * vie. Un utilisateur n'a qu'un rôle ; l'admin les voit tous.
 */
class DashboardController extends Controller
{
    public function __invoke(StatistiqueService $stats)
    {
        $user = Auth::user();
        $estAdmin = $user->hasRole(RoleEnum::admin->value);

        // Blocs affichés : un par rôle, tous pour l'admin.
        $blocs = [
            'commission' => $estAdmin || $user->hasRole(RoleEnum::agentCommission->value),
            'stock' => $estAdmin || $user->hasRole(RoleEnum::gestionnaireStock->value),
        ];

        return view('dashboard', [
            'layout' => Helper::layout(),
            'estAdmin' => $estAdmin,
            'etats' => $stats->demandesParEtat($user),
            'parCampagne' => $stats->demandesParCampagne($user),
            'attributions' => $stats->dernieresAttributions(),
            'commission' => $blocs['commission'] ? $stats->commission() : null,
            'stock' => $blocs['stock'] ? $stats->stock() : null,
            // Le DP émet des demandes pour les acteurs : on lui en facilite l'accès.
            'peutCreer' => $user->can(\App\Enums\PermissionEnum::creerDemande->value),
        ]);
    }
}
