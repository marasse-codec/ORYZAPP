<?php

namespace Database\Seeders;

use App\Domain\Stock\Models\TypeMouvementStock;
use Illuminate\Database\Seeder;

class TypeMouvementStockSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'ENTREE',
                'libelle_type_mouvement' => 'Entrée',
            ],
            [
                'code' => 'SORTIE',
                'libelle_type_mouvement' => 'Sortie',
            ],
            [
                'code' => 'RETOUR',
                'libelle_type_mouvement' => 'Retour',
            ],
            [
                'code' => 'AJUSTEMENT',
                'libelle_type_mouvement' => 'Ajustement',
            ],
        ];

        foreach ($types as $type) {
            TypeMouvementStock::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
