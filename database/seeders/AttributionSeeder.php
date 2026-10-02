<?php

namespace Database\Seeders;

use App\Enums\StatutAttributionEnum;
use App\Models\Appui;
use App\Models\Attribution;
use App\Models\Demande;
use App\Models\LigneAttribution;
use App\Models\SessionCommission;
use Illuminate\Database\Seeder;

class AttributionSeeder extends Seeder
{
    public function run(): void
    {
        // Récupération des prérequis
        $demandes = Demande::all();
        $sessions = SessionCommission::all();
        $appuis = Appui::all();

        if ($demandes->isEmpty() || $appuis->isEmpty()) {
            $this->command->warn('DemandeSeeder et AppuiSeeder doivent être exécutés avant AttributionSeeder.');
            return;
        }

        $sessionActive = $sessions->first();

        // Récupération sécurisée des IDs (évite l'erreur "Undefined array key")
        // Si vous avez moins de 4 demandes ou 3 appuis en base, on prévoit un fallback
        $d1 = $demandes->get(0)?->id_demande ?? 1;
        $d2 = $demandes->get(1)?->id_demande ?? 2;
        $d3 = $demandes->get(2)?->id_demande ?? 3;
        $d4 = $demandes->get(3)?->id_demande ?? 4;

        $a1 = $appuis->get(0)?->id_appui ?? 1;
        $a2 = $appuis->get(1)?->id_appui ?? 2;
        $a3 = $appuis->get(2)?->id_appui ?? 3;

        // 1. Jeu de données représentatif des différents états d'attribution
        $attributionsData = [
            [
                'id_attribution' => 1,
                'id_demande' => $d1,
                'id_session' => $sessionActive?->id_session,
                'statut' => StatutAttributionEnum::validee,
                'commentaires' => 'Attribution validée lors de la commission générale.',
                'remise' => 5.00, // 5% de remise
                'date_publication' => now()->subDays(2),
                'date_destockage' => now()->subDay(),
                'numero_piece_identite' => 'CI-001298383',
                'chemin_piece_identite' => 'identites/ci_001298383.pdf',
                'lignes' => [
                    ['id_appui' => $a1, 'quantite' => 10, 'cout' => 500.00],
                    ['id_appui' => $a2, 'quantite' => 5, 'cout' => 600.00],
                ],
            ],
            [
                'id_attribution' => 2,
                'id_demande' => $d2,
                'id_session' => $sessionActive?->id_session,
                'statut' => StatutAttributionEnum::soumise,
                'commentaires' => 'En attente de délibération finale de la commission.',
                'remise' => 0.00,
                'date_publication' => null,
                'date_destockage' => null,
                'numero_piece_identite' => 'CI-004829102',
                'chemin_piece_identite' => 'identites/ci_004829102.pdf',
                'lignes' => [
                    ['id_appui' => $a3, 'quantite' => 1, 'cout' => 850000.00],
                ],
            ],
            [
                'id_attribution' => 3,
                'id_demande' => $d3,
                'id_session' => null,
                'statut' => StatutAttributionEnum::brouillon,
                'commentaires' => 'Brouillon initialisé par l\'agent.',
                'remise' => 0.00,
                'date_publication' => null,
                'date_destockage' => null,
                'numero_piece_identite' => null,
                'chemin_piece_identite' => null,
                'lignes' => [
                    ['id_appui' => $a2, 'quantite' => 20, 'cout' => 600.00],
                ],
            ],
            [
                'id_attribution' => 4,
                'id_demande' => $d4,
                'id_session' => $sessionActive?->id_session,
                'statut' => StatutAttributionEnum::annulee,
                'commentaires' => 'Rejetée en raison d\'un dossier incomplet.',
                'remise' => 0.00,
                'date_publication' => null,
                'date_destockage' => null,
                'numero_piece_identite' => null,
                'chemin_piece_identite' => null,
                'lignes' => [
                    ['id_appui' => $a1, 'quantite' => 2, 'cout' => 500.00],
                ],
            ],
        ];

        // 2. Traitement et insertion
        foreach ($attributionsData as $data) {
            $attribution = Attribution::updateOrCreate(
                ['id_attribution' => $data['id_attribution']],
                [
                    'id_demande' => $data['id_demande'],
                    'id_session' => $data['id_session'],
                    'commentaires' => $data['commentaires'],
                    'remise' => $data['remise'],
                    'date_destockage' => $data['date_destockage'],
                    'numero_piece_identite' => $data['numero_piece_identite'],
                    'chemin_piece_identite' => $data['chemin_piece_identite'],
                    'id_statut_attribution' => $data['statut'],
                    'date_publication' => $data['date_publication'],
                ]
            );

            // 3. Création des lignes d'attribution associées
            foreach ($data['lignes'] as $ligne) {
                LigneAttribution::updateOrCreate(
                    [
                        'id_attribution' => $attribution->id_attribution,
                        'id_appui' => $ligne['id_appui'],
                    ],
                    [
                        'quantite_attribuee' => $ligne['quantite'],
                        'cout_attribution' => $ligne['cout'],
                    ]
                );
            }
        }
    }
}