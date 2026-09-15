<?php

namespace App\Livewire;

use App\Models\Appui;
use Livewire\Component;
use App\Models\ReferenceAppui;
use App\Models\Equipement;
use Illuminate\Support\Facades\DB;

class PointStocksWidget extends Component
{
    public function render()
    {
        // 1. Récupération des appuis consommables et état de leurs stocks
        $appuis = Appui::query()
            ->withCount(['equipements as total_equipements'])
            ->withCount(['equipements as equipements_disponibles' => function ($query) {
                $query->where('en_service', false);
            }])
            ->get()
            ->map(function ($appui) {
                // Quantité réservée/engagée sur les bons d'attribution non encore enlevés
                $quantiteEngagee = DB::table('ligne_bon_attributions')
                    ->where('id_reference_appui', $appui->id_reference_appui)
                    ->sum('quantite_attribuee');

                $stockDispo = $appui->quantite_en_stock - $quantiteEngagee;

                return [
                    'id' => $appui->id_reference_appui,
                    'libelle' => $appui->libelle_reference_appui,
                    'est_consommable' => $appui->est_consommable,
                    'stock_physique' => $appui->quantite_en_stock,
                    'quantite_engagee' => $quantiteEngagee,
                    'stock_disponible' => $stockDispo,
                    'en_alerte' => $stockDispo <= 0,
                    'equipements_totaux' => $appui->total_equipements,
                    'equipements_parking' => $appui->equipements_disponibles,
                ];
            });

        // 2. Total récapitulatif du matériel roulant/équipements (Non consommables)
        $totalEquipements = Equipement::count();
        $equipementsEnService = Equipement::where('en_service', true)->count();
        $equipementsAuParking = $totalEquipements - $equipementsEnService;

        return view('components.point-stocks-widget', [
            'appuis' => $appuis,
            'totalEquipements' => $totalEquipements,
            'equipementsEnService' => $equipementsEnService,
            'equipementsAuParking' => $equipementsAuParking,
        ]);
    }
}