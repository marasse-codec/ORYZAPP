<?php

namespace App\Http\Controllers\Suivi;

use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Suivi\Services\ComparaisonCampagnesService;
use App\Domain\Suivi\Services\PilotageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Suivi\StatistiqueRequest;
use App\Utils\Helper;

/** Le tableau de pilotage du directeur (lecture seule). */
class PilotageController extends Controller
{
    public function index(StatistiqueRequest $request, PilotageService $pilotage)
    {
        $campagne = $request->campagne();

        return view('pilotage.index', [
            'layout' => Helper::layout(),
            'campagne' => $campagne,
            'campagnes' => Campagne::orderByDesc('mois_debut_campagne')->get(),
            'tableau' => $campagne ? $pilotage->tableau($campagne) : null,
            'indicateursComparables' => ComparaisonCampagnesService::INDICATEURS,
        ]);
    }
}
