<?php

namespace Database\Seeders;

use App\Models\Appui;
use App\Models\UniteAppui;
use Illuminate\Database\Seeder;

class AppuiSeeder extends Seeder
{
    public function run(): void
    {
        $unites = [
            1 => 'gramme',
            2 => 'millilitre',
            3 => 'unité',
        ];

        foreach ($unites as $id => $libelle) {
            UniteAppui::create(['id_unite_appui' => $id, 'lib_unite_appui' => $libelle]);
        }

        $appuis = [
            // [id, libellé, type d'appui, stock, coût unitaire]
            [1, 'Semences NPK', 1, 120, 500, 1],
            [2, 'Semences améliorées', 1, 80, 750, 2],
            [3, 'Urée', 2, 120, 600, 1],
            [4, 'Engrais NPK 15-15-15', 2, 200, 550, 2],
            [5, 'Insecticide polyvalent', 3, 45, 3500, 1],
            [6, 'Herbicide sélectif', 3, 60, 2800, 2],
            [7, 'Tracteur 20 chevaux', 4, 3, 4500000, 3],
            [8, 'Charrue 40 chevaux', 5, 5, 850000, 3],
            [9, 'Motopompe', 6, 12, 320000, 3],
        ];

        foreach ($appuis as [$id, $libelle, $idTypeAppui, $stock, $cout, $idUnite]) {
            $appui = Appui::create([
                'id_appui' => $id,
                'libelle' => $libelle,
                'id_type_appui' => $idTypeAppui,
                'quantite_en_stock' => $stock,
                'cout_unitaire' => $cout
            ]);

            if (in_array($idTypeAppui, [4, 5, 6])) {
                // Créer des équipements associés pour les appuis de type équipement
                for ($i = 1; $i <= 3; $i++) {
                    $appui->equipements()->create([
                        'matricule_equipement' => strtoupper(substr($libelle, 0, 3)).'-'.str_pad($i, 3, '0', STR_PAD_LEFT),
                        'etat' => 'neuf',
                        'en_service' => false,
                    ]);
                }
            }
        }
    }
}
