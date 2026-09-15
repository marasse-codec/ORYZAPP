<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Localite;
use Illuminate\Database\Seeder;

class LocaliteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Departement::create([
            'id_departement' => 1,
            'lib_departement' => 'Departement 1',
        ]);

        Departement::create([
            'id_departement' => 2,
            'lib_departement' => 'Departement 2',
        ]);

        // 2. (Optionnel) Créer des données spécifiques "en dur"
        Localite::create([
            'id_localite' => 1,
            'lib_localite' => 'Localite 1',
            'id_departement' => 1,
        ]);

        Localite::create([
            'id_localite' => 2,
            'lib_localite' => 'Localite 2',
            'id_departement' => 1,
        ]);

        Localite::create([
            'id_localite' => 3,
            'lib_localite' => 'Localite 3',
            'id_departement' => 2,
        ]);

        Localite::create([
            'id_localite' => 4,
            'lib_localite' => 'Localite 4',
            'id_departement' => 2,
        ]);

    }
}
