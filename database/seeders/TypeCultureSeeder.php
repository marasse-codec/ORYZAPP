<?php

namespace Database\Seeders;

use App\Models\TypeCulture;
use Illuminate\Database\Seeder;

class TypeCultureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 2. (Optionnel) Créer des données spécifiques "en dur"
        TypeCulture::create([
            'id_type_culture' => 1,
            'lib_type_culture' => 'PLUVIAL PLATEAU',
        ]);

        TypeCulture::create([
            'id_type_culture' => 2,
            'lib_type_culture' => 'PLUVIAL BAS-FOND',
        ]);

        TypeCulture::create([
            'id_type_culture' => 3,
            'lib_type_culture' => 'BAS-FOND IRRIGUÉ',
        ]);
    }
}
