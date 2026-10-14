<?php

namespace Database\Seeders;

use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Demande\Models\SystemeProduction;
use App\Domain\Demande\Models\Traitement;
use App\Domain\Referentiel\Models\Campagne;
use Illuminate\Database\Seeder;

class DemandeSeeder extends Seeder
{
    public function run(): void
    {
        $acteurs = Acteur::all();

        if ($acteurs->isEmpty()) {
            $this->command->warn('ActeurSeeder doit être exécuté avant DemandeSeeder.');
            return;
        }

        $campagne = Campagne::first();
        $idCampagne = $campagne?->id_campagne ?? 1;

        // Préparation de 4 jeux de données représentatifs
        $demandesData = [
            [
                'id_demande' => 1,
                'id_acteur' => $acteurs->get(0)?->id_acteur ?? 1,
                'nom_structure' => 'Coopérative du centre',
                'id_type_acteur' => 3, // Coopérative
                'date_demande' => now()->subDays(10)->toDateString(),
                'periode_debut_mise_en_place' => '2026-10-30',
                'periode_fin_mise_en_place' => '2027-01-30',
                'date_soummission' => null,
                'id_user' => 2,
                'localites' => [1, 2],
                'systemes' => [1 => 12.5, 2 => 8.0], // [id_type_systeme_production => superficie]
                'lignes' => [ 
                    [0.8, null, 1, 1],
                    [1, false, 4, 4],
                    [1, true, 3, 3],
                     ],                 // ids_types_appuis
                'services' => [1, 2],               // ids_services
            ],
            // [
            //     'id_demande' => 2,
            //     'id_acteur' => $acteurs->get(1)?->id_acteur ?? $acteurs->get(0)?->id_acteur ?? 1,
            //     'id_type_acteur' => 1, // Producteur individuel
            //     'date_demande' => now()->subDays(6)->toDateString(),
            //     'periode_debut_mise_en_place' => '2026-11-15',
            //     'periode_fin_mise_en_place' => '2027-02-15',
            //     'date_soummission' => now()->subDays(5),
            //     'id_user' => 2,
            //     'localites' => [1],
            //     'systemes' => [1 => 5.0],
            //     'lignes' => [2, 3],
            //     'services' => [1],
            // ],
            // [
            //     'id_demande' => 3,
            //     'id_acteur' => $acteurs->get(2)?->id_acteur ?? $acteurs->get(0)?->id_acteur ?? 1,
            //     'id_type_acteur' => 2, // Groupement
            //     'date_demande' => now()->subDays(3)->toDateString(),
            //     'periode_debut_mise_en_place' => '2026-12-01',
            //     'periode_fin_mise_en_place' => '2027-03-01',
            //     'date_soummission' => null, // Demande en brouillon
            //     'id_user' => 2,
            //     'localites' => [2],
            //     'systemes' => [2 => 15.0, 3 => 10.0],
            //     'lignes' => [1, 2],
            //     'services' => [2],
            // ],
            // [
            //     'id_demande' => 4,
            //     'id_acteur' => $acteurs->get(3)?->id_acteur ?? $acteurs->get(0)?->id_acteur ?? 1,
            //     'id_type_acteur' => 3, // Coopérative
            //     'date_demande' => now()->subDay()->toDateString(),
            //     'periode_debut_mise_en_place' => '2026-11-01',
            //     'periode_fin_mise_en_place' => '2027-02-01',
            //     'date_soummission' => now()->subDay(),
            //     'id_user' => 2,
            //     'localites' => [1, 2],
            //     'systemes' => [1 => 20.0, 3 => 25.0],
            //     'lignes' => [3, 4],
            //     'services' => [1, 2],
            // ],
        ];

        foreach ($demandesData as $data) {
            // 1. Création / Mise à jour de la demande
            $demande = Demande::updateOrCreate(
                ['id_demande' => $data['id_demande']],
                [
                    'id_acteur' => $data['id_acteur'],
                    'id_type_acteur' => $data['id_type_acteur'],
                    'date_demande' => $data['date_demande'],
                    'periode_debut_mise_en_place' => $data['periode_debut_mise_en_place'],
                    'periode_fin_mise_en_place' => $data['periode_fin_mise_en_place'],
                    'date_soummission' => $data['date_soummission'],
                    'id_user' => $data['id_user'],
                    'id_campagne' => $idCampagne,
                ]
            );

            // 2. Association des localités
            $demande->localites()->sync($data['localites']);

            // 3. Superficies ventilées par système de production
            foreach ($data['systemes'] as $idTypeSysteme => $superficie) {
                SystemeProduction::updateOrCreate(
                    [
                        'id_demande' => $demande->id_demande,
                        'id_type_systeme_production' => $idTypeSysteme,
                    ],
                    [
                        'superficie' => $superficie,
                    ]
                );
            }

            // 4. Appuis sollicités
            foreach ($data['lignes'] as [$quantiteDemandee, $cession, $idTypeAppui, $idAppui]) {
                LigneDemande::updateOrCreate(
                    [
                        'id_demande' => $demande->id_demande,
                        'id_type_appui' => $idTypeAppui,
                        'quantite_demandee' => $quantiteDemandee,
                        'id_appui' => $idAppui
                    ]
                );
            }

            // 5. Traitements par service
            foreach ($data['services'] as $idService) {
                Traitement::updateOrCreate(
                    [
                        'id_service' => $idService,
                        'id_demande' => $demande->id_demande,
                    ]
                );
            }
        }
    }
}