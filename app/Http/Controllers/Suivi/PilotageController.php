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
        $annee = $request->annee($campagne);

        return view('pilotage.index', [
            'layout' => Helper::layout(),
            'campagne' => $campagne,
            // On choisit l'année, puis l'un de ses cycles.
            'annees' => Campagne::annees(),
            'annee' => $annee,
            'cycles' => $annee ? Campagne::deLAnnee($annee)->get() : collect(),
            'tableau' => $campagne ? $pilotage->tableau($campagne) : null,
            'indicateursComparables' => ComparaisonCampagnesService::INDICATEURS,
        ]);
    }
}
