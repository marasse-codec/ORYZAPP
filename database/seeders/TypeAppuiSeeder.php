<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\TypeAppui;
use Illuminate\Database\Seeder;

class TypeAppuiSeeder extends Seeder
{
    public function run(): void
    {

        // Seuls les appuis quantifiables et stockables sont gérés : conseils,
        // études et prestations immatérielles ne se destockent pas.
        $types = [
            // [id, libellé, service, intrant, est_stockable]
            [1, 'Semences', 1, true, true],
            [2, 'Urée', 1, true, true],
            [3, 'Charrue', 2, false, true],
            [4, 'Tracteur', 2, false, true],
            [5, 'Moissoneuse', 2, false, true],
        ];

        foreach ($types as [$id, $libelle, $idService, $estIntrant, $estStockable]) {
            TypeAppui::create([
                'id_type_appui' => $id,
                'lib_type_appui' => $libelle,
                'id_service' => $idService,
                'est_intrant' => $estIntrant,
            ]);
        }
    }
}
