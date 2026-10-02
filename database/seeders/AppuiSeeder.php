<?php

namespace Database\Seeders;

use App\Models\Appui;
use App\Models\UniteDosage;
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

        foreach ($unites as $id => $libelle) {
            $u = new UniteDosage();
            $u->code_unite = $id;
            $u->lib_unite = $libelle;
            $u->save();
        }

        // 2. Liste des appuis
        // Structure : [id, libelle, id_type_appui, stock, cout_unitaire, [ids_types_systemes_productions]]
        $appuis = [
            // Appui 1 : disponible pour les systèmes de production 1 et 2 (ex: Intensif, Semi-intensif)
            [1, 'Semences NPK', 1, 120, 500.00, 4, 0.040, [1, 2], 1],
            
            // Appui 2 : disponible pour les systèmes 1, 2 et 3
            [2, 'Urée liquide', 2, 120, 600.00, 4, 0.150, [1, 2, 3], 2],
            
            // Appui 3 : disponible pour le système 3
            [3, 'Charrue 40 chevaux', 3, 5, 850000.00, 3, 1.000, [3], 3],
            
            // Appui 4 : disponible pour le système 3
            [4, 'Tracteur 75 CV', 4, 12, 3200000.00, 3, 1.000, [3], 3],
        ];

        foreach ($appuis as [$id, $libelle, $idTypeAppui, $stock, $cout, $idUniteAppui, $doseHa, $systemeIds, $idUnite]) {
            
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

            // 3. Liaison avec les Types de Systèmes de Production
            if (!empty($systemeIds)) {
                $appui->typesSystemesProductions()->sync($systemeIds);
            }

            // 4. Génération des équipements associés
            if (in_array($idTypeAppui, [3, 4])) {
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