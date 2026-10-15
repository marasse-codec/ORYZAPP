<?php

namespace App\Http\Controllers\Commission;

use App\Domain\Commission\Services\ReglementService;
use App\Http\Controllers\Controller;
use App\Utils\Helper;

/**
 * La page « Règlements » du comptable : ce qui est dû et encaissé, les acteurs
 * en impayé, les attributions non soldées et les derniers encaissements.
 * Le règlement lui-même s'enregistre sur la fiche de l'attribution.
 */
class ReglementController extends Controller
{
    public function index(ReglementService $reglements)
    {
        return view('reglements.index', [
            'layout' => Helper::layout(),
            'reglements' => [
                'synthese' => $reglements->synthese(),
                'acteurs' => $reglements->acteursEnImpaye(),
                'attributions' => $reglements->attributionsImpayees(),
            ],
            'paiements' => $reglements->derniersPaiements(),
        ]);
    }
}
