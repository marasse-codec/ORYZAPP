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
            'lib_departement' => 'Abengourou',
        ]);

        Departement::create([
            'id_departement' => 2,
            'lib_departement' => 'Agboville',
        ]);

        Departement::create([
            'id_departement' => 3,
            'lib_departement' => 'Bouake',
        ]);

        // 2. (Optionnel) Créer des données spécifiques "en dur"
        Localite::create([
            'lib_localite' => 'Kongodjan',
            'id_departement' => 1,
        ]);

        Localite::create([
            'lib_localite' => 'Koikoukro',
            'id_departement' => 1,
        ]);

        Localite::create([
            'lib_localite' => 'Agboville',
            'id_departement' => 2,
        ]);

        Localite::create([
            'lib_localite' => 'M\'bahiakro',
            'id_departement' => 3,
        ]);

        Localite::create([
            'lib_localite' => 'Yabra',
            'id_departement' => 3,
        ]);
    }
}
