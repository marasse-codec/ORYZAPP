<?php

namespace Database\Seeders;

use App\Models\Acteur;
use App\Models\Attribution;
use App\Models\Demande;
use App\Models\LigneAttribution;
use App\Models\LigneDemande;
use App\Models\Rapport;
use App\Models\SessionCommission;
use App\Models\SystemeProduction;
use Illuminate\Database\Seeder;

/**
 * Jeu de démonstration : promène deux dossiers supplémentaires à travers le
 * cycle complet, pour que chaque écran ait de quoi s'afficher. Volontairement
 * hors du DatabaseSeeder — les tests partent d'un état neutre.
 *
 * Usage : php artisan db:seed --class=DemonstrationSeeder
 */
class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        $session = SessionCommission::firstOrCreate(
            ['date_debut_session' => today()->subDays(7)->toDateString()],
            ['resume' => 'Séance de quinzaine — dossiers semences et mécanisation']
        );

        // 1. Un dossier instruit, attribué, et déjà servi au magasin.
        $servi = $this->creerDemande(
            nom: 'Groupement Wognon de Gagnoa',
            telephone: '0701020304',
            idTypeActeur: 2,
            idLocalite: 2,
            superficies: [1 => 6.0, 3 => 4.5],
            lignes: [3 => 40, 4 => 25],
        );
        $this->instruire($servi, accorde: true);
        $attribution = $this->attribuer($servi, $session, [
            ['id_appui' => 3, 'quantite' => 30, 'cout' => 600, 'remise' => 5],
            ['id_appui' => 4, 'quantite' => 20, 'cout' => 550, 'remise' => 0],
        ]);

        // 2. Un dossier attribué, en attente de retrait au magasin.
        $aServir = $this->creerDemande(
            nom: 'EARL Bandama',
            telephone: '0705060708',
            idTypeActeur: 5,
            idLocalite: 3,
            superficies: [2 => 22.0],
            lignes: [1 => 200, 9 => 2],
        );
        $this->instruire($aServir, accorde: true);
        $this->attribuer($aServir, $session, [
            ['id_appui' => 1, 'quantite' => 150, 'cout' => 500, 'remise' => 10],
            ['id_appui' => 9, 'quantite' => 1, 'cout' => 320000, 'remise' => 0],
        ]);

        // La séance close rend les deux bons servables.
        $session->update(['date_fin_session' => today()->subDays(2)->toDateString()]);

        // Puis l'un des deux est effectivement retiré.
        $this->servir($attribution, 'Kouassi N\'Guessan');

        // 3. Deux dossiers instruits favorablement qui attendent l'arbitrage :
        //    ils alimentent la synthèse de commission.
        foreach ([
            ['Coopérative Yamoussoukro Nord', '0721222324', 3, 1, [1 => 18.0], [1 => 60, 3 => 80]],
            ['GVC Bouaké Centre', '0725262728', 2, 2, [2 => 9.5, 3 => 3.0], [1 => 45, 6 => 30]],
        ] as [$nom, $telephone, $idTypeActeur, $idLocalite, $superficies, $lignes]) {
            $this->instruire(
                $this->creerDemande($nom, $telephone, $idTypeActeur, $idLocalite, $superficies, $lignes),
                accorde: true,
            );
        }

        // 4. Un dossier encore en instruction, pour l'écran des agents.
        $enCours = $this->creerDemande(
            nom: 'Coopérative Aghien',
            telephone: '0709101112',
            idTypeActeur: 3,
            idLocalite: 4,
            superficies: [1 => 15.0, 2 => 5.0],
            lignes: [2 => 90, 8 => 1],
        );
        $this->instruire($enCours, accorde: false);

        // 5. Un dossier rejeté, motif à l'appui.
        $rejete = $this->creerDemande(
            nom: 'Sanogo Adama',
            telephone: '0713141516',
            idTypeActeur: 1,
            idLocalite: 1,
            superficies: [3 => 1.5],
            lignes: [7 => 1],
        );
        $this->instruire($rejete, accorde: true);
        $rejete->update([
            'date_rejet' => today()->subDays(2)->toDateString(),
            'motifs_rejet' => "Superficie déclarée insuffisante au regard du barème d'attribution d'un tracteur.",
        ]);
    }

    private function creerDemande(
        string $nom,
        string $telephone,
        int $idTypeActeur,
        int $idLocalite,
        array $superficies,
        array $lignes,
    ): Demande {
        $acteur = Acteur::firstOrCreate(['telephone' => $telephone], ['nom' => $nom]);

        $demande = Demande::create([
            'id_acteur' => $acteur->id_acteur,
            'id_type_acteur' => $idTypeActeur,
            'id_localite' => $idLocalite,
            'date_demande' => today()->subDays(14)->toDateString(),
            'debut_periode_mise_en_place' => today()->addMonth()->toDateString(),
        ]);

        foreach ($superficies as $idType => $superficie) {
            SystemeProduction::create([
                'id_demande' => $demande->id_demande,
                'id_type_systeme_production' => $idType,
                'superficie' => $superficie,
            ]);
        }

        foreach ($lignes as $idAppui => $quantite) {
            LigneDemande::create([
                'id_demande' => $demande->id_demande,
                'id_appui' => $idAppui,
                'quantite_demandee' => $quantite,
            ]);
        }

        return $demande->refresh();
    }

    private function instruire(Demande $demande, bool $accorde): void
    {
        foreach ($demande->servicesConcernes() as $index => $service) {
            Rapport::updateOrCreate(
                ['id_demande' => $demande->id_demande, 'id_service' => $service->id_service],
                [
                    'commentaire' => $accorde
                        ? 'Dossier conforme, bon pour commission.'
                        : 'Superficie à confirmer après visite de la parcelle.',
                    'date_rapport' => today()->subDays(10)->toDateString(),
                    // Sans accord, seul le premier service s'est prononcé.
                    'accord_service' => $accorde ? today()->subDays(10)->toDateString() : null,
                ]
            );

            if (! $accorde) {
                break;
            }
        }
    }

    private function attribuer(Demande $demande, SessionCommission $session, array $lignes): Attribution
    {
        $attribution = Attribution::create([
            'id_demande' => $demande->id_demande,
            'id_session' => $session->id_session,
            'commentaires' => 'Quantités ajustées au regard des disponibilités et de la superficie déclarée.',
        ]);

        foreach ($lignes as $ligne) {
            LigneAttribution::create([
                'id_attribution' => $attribution->id_attribution,
                'id_appui' => $ligne['id_appui'],
                'quantite_attribuee' => $ligne['quantite'],
                'cout_attribution' => $ligne['cout'],
                'remise' => $ligne['remise'],
            ]);
        }

        $demande->update(['date_attribution' => today()->subDays(3)->toDateString()]);

        return $attribution;
    }

    private function servir(Attribution $attribution, string $recipiendaire): void
    {
        $attribution->loadMissing('lignesAttributions.appui');

        foreach ($attribution->lignesAttributions as $ligne) {
            $ligne->appui?->decrement('quantite_en_stock', $ligne->quantite_attribuee);
        }

        $attribution->update([
            'date_destockage' => today()->subDay()->toDateString(),
            'recipiendaire' => $recipiendaire,
            'numero_piece_identite' => 'CI-2026-004471',
        ]);
    }
}
