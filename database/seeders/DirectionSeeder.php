<?php

namespace Database\Seeders;

use App\Models\Direction;
use Illuminate\Database\Seeder;

class DirectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 2. (Optionnel) Créer des données spécifiques "en dur"
        // La DAP et la DAV n'attribuent qu'après passage en commission ;
        // la DI traite ses copies de demandes directement.
        Direction::create([
            'id_direction' => 1,
            'lib_direction' => 'Direction Appui à la Production',
            'requiert_commission' => true,
        ]);

        Direction::create([
            'id_direction' => 2,
            'lib_direction' => 'Direction Appui à la Valorisation',
            'requiert_commission' => true,
        ]);

        Direction::create([
            'id_direction' => 3,
            'lib_direction' => 'Direction Infrastructure',
            'requiert_commission' => false,
        ]);
    }
}
