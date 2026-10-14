<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\TypeActeur;
use Illuminate\Database\Seeder;

class TypeActeurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 2. (Optionnel) Créer des données spécifiques "en dur"
        TypeActeur::create([
            'id_type_acteur' => 1,
            'code_type_acteur' => 'PIN',
            'lib_type_acteur' => 'Producteur Individuel',
        ]);

        TypeActeur::create([
            'id_type_acteur' => 2,
            'code_type_acteur' => 'GIN',

            'lib_type_acteur' => 'Groupement Informel',
        ]);

        TypeActeur::create([
            'id_type_acteur' => 3,
            'code_type_acteur' => 'COO',

            'lib_type_acteur' => 'Coopérative',
        ]);

        TypeActeur::create([
            'id_type_acteur' => 4,
            'code_type_acteur' => 'EAG',
            'lib_type_acteur' => 'Entreprise agricole',
        ]);

        TypeActeur::create([
            'id_type_acteur' => 5,
            'code_type_acteur' => 'ONG',
            'lib_type_acteur' => 'Organisation Non Gouvernemental',
        ]);
    }
}
