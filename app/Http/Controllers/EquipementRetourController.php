<?php

namespace App\Http\Controllers;

use App\Enums\TypeMouvementStockEnum;
use App\Models\Agent;
use App\Models\Equipement;
use App\Models\MouvementStock;
use App\Models\TypeMouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    /**
     * Enregistrement du retour d'un équipement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'retours'                 => ['required', 'array', 'min:1'],
            'retours.*.id_equipement' => ['required', 'exists:equipements,id_equipement'],
            'retours.*.etat'          => ['required', 'in:Bon état,Abîmé,Défectueux'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['retours'] as $retour) {
                $equipement = Equipement::findOrFail($retour['id_equipement']);
                
                // Mise à jour de l'équipement
                $equipement->update([
                    'etat' => $retour['etat'],
                    'disponible' => true, // Ou autre logique métier
                ]);

                // Optionnel : Enregistrer un historique/mouvement de restitution
                // Restitution::create([...]);
            }
        });

        return redirect()->route('equipements.index')
            ->with('success', count($validated['retours']) . ' équipement(s) restitué(s) avec succès.');
    }
}