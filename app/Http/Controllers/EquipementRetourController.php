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
            'id_equipement' => ['required', 'exists:equipements,id_equipement'],
            'etat'          => ['required', 'string', 'in:Bon état,Abîmé,Défectueux'],
        ]);

        $typeRetour = TypeMouvementStock::where('id_type_mouvement_stock',TypeMouvementStockEnum::RETOUR)->firstOrFail();

        DB::transaction(function () use ($validated, $typeRetour) {
            $equipement = Equipement::findOrFail($validated['id_equipement']);

            $equipement->update([
                'etat'       => $validated['etat'],
                'en_service' => false,
            ]);

            MouvementStock::create([
                'id_type_mouvement_stock' => $typeRetour->id_type_mouvement_stock,
                'quantite'                => 1,
                'date_mouvement'          => now(),
                'id_appui'                => $equipement->id_appui,
                'id_equipement'           => $equipement->id_equipement,
                'id_agent'                => Agent::where('user_id',Auth::id())->value('id_agent'),
            ]);

            // 3. Incrémenter la quantité en stock de l'appui associé (+1)
            if ($equipement->appui) {
                $equipement->appui()->increment('quantite_en_stock', 1);
            }
        });

        return redirect()
            ->route('equipements.index')
            ->with('success', 'Retour enregistré avec succès.');
    }
}