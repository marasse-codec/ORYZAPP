<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\Campagne;
use Illuminate\Database\Seeder;

class CampagneSeeder extends Seeder
{
    public function run(): void
    {
        Campagne::create(
            [
                'lib_campagne' => 'Cycle 1',
                'mois_debut_campagne' => '2026-01-01',
                'mois_fin_campagne' => '2026-04-01'
            ]
        );

        Campagne::create(
            [
                'lib_campagne' => 'Cycle 2',
                'mois_debut_campagne' => '2026-04-01',
                'mois_fin_campagne' => '2026-08-01'
            ]
        );

        Campagne::create(
            [
                'lib_campagne' => 'Cycle 3',
                'mois_debut_campagne' => '2026-08-01',
                'mois_fin_campagne' => '2026-12-01'
            ]
        );
    }
}
