<?php

namespace App\Services;

use App\Enums\TypeMouvementStockEnum;
use App\Models\Agent;
use App\Models\Approvisionnement;
use App\Models\Appui;
use App\Models\Equipement;
use App\Models\MouvementStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovisionnementService
{
    public function store(Request $request)
    {
        // 1. Validation avec règles conditionnelles pour les matricules
        $validated = $request->validate([
            'date_approvisionnement' => 'required|date|before_or_equal:today',
            'reference_commande'     => 'nullable|string|max:100',
            'origine'                => 'nullable|string|max:255',

            'lignes'                             => 'required|array|min:1',
            'lignes.*.id_appui'                  => 'required|exists:appuis,id_appui',
            'lignes.*.quantite_ajoutee'           => 'required|integer|min:1',
            'lignes.*.matricules'                => 'nullable|array',
            'lignes.*.matricules.*'              => 'nullable|string|distinct|max:100|unique:equipements,matricule_equipement',
        ], [
            'lignes.required' => 'Vous devez ajouter au moins un appui.',
            'lignes.*.id_appui.required' => 'Veuillez sélectionner un appui valide.',
            'lignes.*.matricules.*.unique' => 'Un de ces matricules existe déjà en base.',
        ]);

        // 2. Traitement dans une transaction SQL
        DB::transaction(function () use ($validated) {
            $approvisionnement = Approvisionnement::create([
                'date_approvisionnement' => $validated['date_approvisionnement'],
                'reference_commande'     => $validated['reference_commande'] ?? null,
                'origine'                => $validated['origine'] ?? null,
            ]);

            foreach ($validated['lignes'] as $ligne) {
                $appui = Appui::with('typeAppui')->findOrFail($ligne['id_appui']);
                $quantite = (int) $ligne['quantite_ajoutee'];

                // Cas A : L'appui est un Équipement (traité à l'unité si des matricules sont fournis)
                if (!empty($ligne['matricules'])) {
                    foreach ($ligne['matricules'] as $matricule) {
                        if (empty($matricule)) continue;

                        // Création de l'équipement physique
                        $equipement = Equipement::create([
                            'id_appui'             => $appui->id_appui,
                            'matricule_equipement' => $matricule,
                            'etat'                 => 'bon',
                            'en_service'           => false,
                        ]);

                        // Mouvement de stock rattaché à cet équipement précis
                        MouvementStock::create([
                            'id_approvisionnement'    => $approvisionnement->id_approvisionnement,
                            'id_appui'                => $appui->id_appui,
                            'id_equipement'           => $equipement->id_equipement,
                            'quantite'                => 1,
                            'id_agent'                => Agent::where('user_id',Auth::id())->first()->id_agent,
                            'id_type_mouvement_stock' => TypeMouvementStockEnum::ENTREE,
                            'date_mouvement' => $validated['date_approvisionnement']
                        ]);
                    }
                } 
                // Cas B : Appui classique / Consommable (Mouvement de stock global)
                else {
                    MouvementStock::create([
                        'id_approvisionnement'    => $approvisionnement->id_approvisionnement,
                        'id_appui'                => $appui->id_appui,
                        'id_equipement'           => null,
                        'quantite'                => $quantite,
                        'id_agent'                => Auth::id(),
                        'id_type_mouvement_stock' => TypeMouvementStockEnum::ENTREE,
                    ]);
                }

                // Incrémentation globale du stock de l'appui
                $appui->increment('quantite_en_stock', $quantite);
            }
        });

        return redirect()
            ->route('approvisionnements.index')
            ->with('success', 'L\'approvisionnement a été enregistré avec succès.');
    }
}