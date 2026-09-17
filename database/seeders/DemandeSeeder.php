<?php

namespace Database\Seeders;

use App\Enums\StatutDemandeE;
use App\Models\Acteur;
use App\Models\Campagne;
use App\Models\Demande;
use App\Models\LigneDemande;
use App\Models\SystemeProduction;
use App\Models\Traitement;
use Illuminate\Database\Seeder;

class DemandeSeeder extends Seeder
{
    public function run(): void
    {
        $acteur = Acteur::where('telephone', '0758221948')->firstOrFail();

        $demande = Demande::create([
            'id_acteur' => $acteur->id_acteur,
            'id_type_acteur' => 3, // Coopérative
            'id_localite' => 1,
            'date_demande' => now()->toDateString(),
            'periode_debut_mise_en_place' => '2026-10-30',
            'periode_fin_mise_en_place' => '2027-01-30', // 3 mois
            'soumise' => false,
            'id_user' => 2,
            'id_campagne' => Campagne::first()->id_campagne
        ]);

        // Superficies ventilées par système de production.
        foreach ([1 => 12.5, 2 => 8.0] as $idType => $superficie) {
            SystemeProduction::create([
                'id_demande' => $demande->id_demande,
                'id_type_systeme_production' => $idType,
                'superficie' => $superficie,
            ]);
        }

        // Appuis sollicités : deux services distincts, pour que l'instruction
        // ait plus d'un destinataire.
        foreach ([1 => 130, 4 => 20] as $idTypeAppui => $quantite) {

            LigneDemande::create([
                'id_demande' => $demande->id_demande,
                'id_type_appui' => $idTypeAppui
            ]);
        }

        Traitement::create([
            'id_service' => 1,
            'id_demande' => $demande->id_demande,
        ]);

        Traitement::create([
            'id_service' => 2,
            'id_demande' => $demande->id_demande,
        ]);
    }
}
