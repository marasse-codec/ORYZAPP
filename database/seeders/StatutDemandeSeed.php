<?php

namespace Database\Seeders;

use App\Models\StatutDemande;
use Illuminate\Database\Seeder;

class StatutDemandeSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StatutDemande::create([
            'id_statut_demande' => 1,
            'lib_statut_demande' => 'BROUILLON',
        ]);

        StatutDemande::create([
            'id_statut_demande' => 2,
            'lib_statut_demande' => 'SOUMISE',
        ]);

        StatutDemande::create([
            'id_statut_demande' => 3,
            'lib_statut_demande' => 'REÇU_ATTRIBUTION',
        ]);

        StatutDemande::create([
            'id_statut_demande' => 4,
            'lib_statut_demande' => 'REJETEE_COMPLETEMENT',
        ]);

        StatutDemande::create([
            'id_statut_demande' => 5,
            'lib_statut_demande' => 'CORRECTION_EN_TETE',
        ]);

        StatutDemande::create([
            'id_statut_demande' => 6,
            'lib_statut_demande' => 'RESOUMISE',
        ]);

    }
}
