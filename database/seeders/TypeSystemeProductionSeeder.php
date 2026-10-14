<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\TypeSystemeProduction;
use Illuminate\Database\Seeder;

class TypeSystemeProductionSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            1 => 'Riziculture de bas-fond',
            2 => 'Riziculture irriguée',
            3 => 'Riziculture de plateau',
        ];

        foreach ($types as $id => $libelle) {
            TypeSystemeProduction::create([
                'id_type_systeme_production' => $id,
                'lib_type_systeme_production' => $libelle,
            ]);
        }
    }
}
