<?php

namespace App\Http\Controllers\Editions;

use App\Enums\StatutAttributionEnum;
use App\Http\Controllers\Controller;
use App\Models\Attribution;
use Illuminate\Http\Request;

class EditionArbitrageController extends Controller
{
    /**
     * Affiche la liste des attributions en attente d'arbitrage pour la commission.
     */
    public function index()
    {
        $attributions = Attribution::with([
            'session',
            'demande.acteur',
            'demande.typeActeur',
            'demande.localites',
            'lignesAttributions.appui.uniteAppui'
        ])
        ->whereNull('id_session')
        ->whereNull('date_publication')
        ->latest('id_attribution')
        ->get();

        return view('editions.arbitrages.index', compact('attributions'));
    }
    
    /**
     * Génère la fiche d'arbitrage physique pour la commission.
     */
    public function imprimerArbitrage(Request $request)
    {
        $ids = $request->input('attributions', []);

        $query = Attribution::with([
            'session', // <-- Ajout de la relation session
            'demande.acteur',
            'demande.typeActeur',
            'demande.localites',
            'lignesAttributions.appui.uniteAppui'
        ]);

        if (!empty($ids)) {
            // Scénario nominal : attributions sélectionnées
            $attributions = $query->whereIn('id_attribution', $ids)->get();
        } else {
            // Scénario alternatif : toutes les attributions non encore traitées
            $attributions = $query->whereNull('id_session')
                                  ->whereNull('date_publication')
                                  ->get();
        }

        return view('editions.arbitrages.fiche_arbitrage', compact('attributions'));
    }
}