<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Referentiel\Models\TypeSystemeProduction;
use App\Domain\Referentiel\Models\UniteDosage;
use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AppuiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Unités d'appui
        $unites = [
            'T' => 'Tonnes',
            'L' => 'Litre',
            'U' => 'Unité'
        ];

        // Identifiants explicites : les appuis ci-dessous s'y réfèrent, l'auto-incrément ne les garantit pas.
        $idUnite = 1;
        foreach ($unites as $code => $libelle) {
            $u = UniteDosage::firstOrNew(['code_unite' => $code]);
            $u->id_unite = $idUnite++;
            $u->lib_unite = $libelle;
            $u->save();
        }

        // 2. Liste des appuis
        // Structure : [id, libelle, id_type_appui, stock, cout_unitaire, dose_ha, [ids_types_systemes_productions], id_unite]
        $appuis = [
            // Appui 1 : disponible pour les systèmes de production 1 et 2 (ex: Intensif, Semi-intensif)
            [1, 'Semences NPK', 1, 120, 700000.00, 0.040, [1, 2, 3], 1],
            
            // Appui 2 : disponible pour les systèmes 1, 2 et 3
            [2, 'Urée liquide', 2, 120, 600000.00, 0.150, [1, 2, 3], 2],
            
            // Appui 3 : disponible pour le système 3
            [3, 'Charrue 40 chevaux', 3, 5, 600000.00, 1.000, [1,2,3], 3],
            
            // Appui 4 : disponible pour le système 3
            [4, 'Tracteur 75 CV', 4, 12, 320000.00, 1.000, [1,2,3], 3],

            // Intrants : semences adaptées à l'écologie, engrais et herbicide pour toutes.
            [5, 'Semences WITA 9', 1, 90, 650000.00, 0.040, [1, 2], 1],
            [6, 'Semences NERICA L-19', 1, 70, 680000.00, 0.050, [1, 3], 1],
            [7, 'Engrais NPK 15-15-15', 6, 150, 450000.00, 0.200, [1, 2, 3], 1],
            [8, 'Herbicide de post-levée', 7, 400, 6500.00, 3.000, [1, 2, 3], 2],

            // Équipements : les écologies listées ici sont sans effet, un équipement sert toutes les écologies.
            [9, 'Moissoneuse-batteuse', 5, 3, 450000.00, 0.020, [], 3],
            [10, 'Motoculteur 12 CV', 8, 8, 180000.00, 0.100, [], 3],
            [11, 'Batteuse à riz', 9, 6, 150000.00, 0.050, [], 3],
            [12, 'Décortiqueuse à riz', 10, 4, 250000.00, 0.010, [], 3],
        ];

        $toutesLesEcologies = TypeSystemeProduction::pluck('id_type_systeme_production')->all();
        $typesEquipements = TypeAppui::where('est_intrant', false)->pluck('id_type_appui')->all();

        foreach ($appuis as [$id, $libelle, $idTypeAppui, $stock, $cout, $doseHa, $systemeIds, $idUnite]) {
            
            $appui = Appui::updateOrCreate(
                ['id_appui' => $id],
                [
                    'libelle' => $libelle,
                    'id_type_appui' => $idTypeAppui,
                    'quantite_en_stock' => $stock,
                    'cout_unitaire' => $cout,
                    'dose_ha' => $doseHa,
                    'id_unite' => $idUnite
                ]
            );

            // 3. Liaison avec les Types de Systèmes de Production : un équipement (non intrant) sert toutes les écologies.
            if (in_array($idTypeAppui, $typesEquipements)) {
                $systemeIds = $toutesLesEcologies;
            }
            if (!empty($systemeIds)) {
                $appui->typesSystemesProductions()->sync($systemeIds);
            }

            // 4. Génération des équipements associés
            if (in_array($idTypeAppui, $typesEquipements)) {
                if ($appui->equipements()->count() === 0) {
                    $prefixe = strtoupper(Str::slug(substr($libelle, 0, 4), ''));

                    for ($i = 1; $i <= 3; $i++) {
                        $appui->equipements()->create([
                            'matricule_equipement' => $prefixe . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                            'etat' => 'neuf',
                            'en_service' => false,
                        ]);
                    }
                }
            }
        }
    }
}