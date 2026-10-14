<?php

namespace App\Http\Controllers\Stock;

use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Services\RestitutionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\RestituerEquipementsRequest;

class EquipementRetourController extends Controller
{
    /**
     * Formulaire de saisie du retour d'un équipement.
     */
    public function create()
    {
        $equipements = Equipement::where('en_service', true)
            ->with('appui')
            ->get();

        return view('equipements.retour.create', compact('equipements'));
    }

    /** Enregistrement de la restitution d'un ou plusieurs équipements (cf. RestitutionService). */
    public function store(RestituerEquipementsRequest $request, RestitutionService $restitutions)
    {
        $nombre = $restitutions->restituer($request->validated('retours'), $request->user());

        return redirect()->route('equipements.index')
            ->with('success', $nombre.' équipement(s) restitué(s) avec succès.');
    }
}
