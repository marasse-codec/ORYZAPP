<?php

namespace Database\Seeders;

use App\Models\StatutAttribution;
use Illuminate\Database\Seeder;

class StatutAttributionSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StatutAttribution::create([
            'id_statut_attribution' => 1,
            'lib_statut_attribution' => 'BROUILLON',
        ]);

        StatutAttribution::create([
            'id_statut_attribution' => 2,
            'lib_statut_attribution' => 'SOUMISE',
        ]);

        StatutAttribution::create([
            'id_statut_attribution' => 3,
            'lib_statut_attribution' => 'VALIDEE',
        ]);

        StatutAttribution::create([
            'id_statut_attribution' => 4,
            'lib_statut_attribution' => 'ANNULEE',
        ]);

    }
}
