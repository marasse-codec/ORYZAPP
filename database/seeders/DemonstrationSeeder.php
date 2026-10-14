<?php

namespace Database\Seeders;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Demande\Models\Traitement;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Agent;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Jeu de démonstration : des dossiers à chaque étape du cycle de vie, sur deux
 * campagnes, pour que le tableau de bord et les statistiques aient de quoi
 * s'afficher. Volontairement hors du DatabaseSeeder : les tests partent d'un
 * état neutre.
 *
 * S'appuie sur les référentiels du DatabaseSeeder (acteur, services, appuis,
 * campagnes, séance ouverte). Tout s'écrit dans une transaction : le jeu est
 * complet ou absent. Les observers (notifications) sont coupés.
 *
 * Usage : php artisan db:seed --class=DemonstrationSeeder
 */
class DemonstrationSeeder extends Seeder
{
    /** Préfixe qui reconnaît les dossiers de démonstration (et évite de les semer deux fois). */
    private const MARQUE = '[Démo] ';

    /**
     * Les dossiers. Chaque ligne : appui => [type d'appui, quantité demandée, quantité attribuée ou null].
     * Quatre statuts d'attribution y figurent : en séance, validée, publiée, retirée.
     * Les dossiers « retiré » ne portent que des intrants (1 et 2) : le retrait des
     * équipements exige de choisir les matricules, ce que la démo n'a pas à simuler.
     * Les quantités attribuées tiennent dans le stock du jeu de base (120 par intrant),
     * y compris celles qui restent à retirer : le stock ne descend jamais sous zéro.
     */
    private const DOSSIERS = [
        // [nom, type d'acteur, campagne, état, localités, lignes]
        ['Groupement Wognon', 2, 'passee', 'retire', [1, 2], [1 => [1, 100, 40], 2 => [2, 60, 30]]],
        ['EARL Bandama', 5, 'passee', 'retire', [3], [1 => [1, 200, 30]]],
        ['Coopérative Sassandra', 3, 'passee', 'rejete', [2], [3 => [3, 4, null]]],

        ['Coopérative du centre', 3, 'courante', 'retire', [1, 2], [1 => [1, 150, 30], 2 => [2, 80, 20]]],
        ['Producteur Kouadio', 1, 'courante', 'a_retirer', [1], [1 => [1, 50, 15], 3 => [3, 2, 1]]],
        ['Union des riziculteurs', 3, 'courante', 'a_retirer', [3], [4 => [4, 2, 1]]],
        ['Ferme Yao', 1, 'courante', 'seance_ouverte', [2], [2 => [2, 30, 30]]],
        ['Producteur Bamba', 1, 'courante', 'validee', [1], [2 => [2, 15, 10]]],
        ['SCOOPS Gagnoa', 3, 'courante', 'en_attente', [1, 3], [1 => [1, 300, null], 3 => [3, 3, null]]],
        ['Producteur Traoré', 1, 'courante', 'en_attente', [2], [2 => [2, 25, null]]],
        ['Groupement Bakayoko', 2, 'courante', 'rejete', [3], [4 => [4, 1, null]]],
        ['Producteur Konan', 1, 'courante', 'brouillon', [1], [1 => [1, 40, null]]],
    ];

    public function run(): void
    {
        if (Demande::where('nom_structure', 'like', self::MARQUE.'%')->exists()) {
            $this->command?->warn('Le jeu de démonstration existe déjà : rien à faire.');

            return;
        }

        DB::transaction(function () {
            // Chaque création déclencherait une notification : inutile ici.
            Demande::withoutEvents(fn () => Attribution::withoutEvents(fn () => Appui::withoutEvents(fn () => $this->semer())));
        });
    }

    private function semer(): void
    {
        $courante = Campagne::enCours();
        $passee = Campagne::where('id_campagne', '!=', $courante->id_campagne)
            ->where('mois_fin_campagne', '<=', $courante->mois_debut_campagne)
            ->orderByDesc('mois_debut_campagne')->first() ?? $courante;

        // Une séance close par campagne ; la séance ouverte du jeu de base reçoit les arbitrages en cours.
        $seancePassee = SessionCommission::create([
            'resume' => 'Séance de la campagne précédente.',
            'date_debut_session' => $passee->mois_debut_campagne->copy()->addMonth(),
            'date_fin_session' => $passee->mois_debut_campagne->copy()->addMonth()->addDays(14),
        ]);
        $seanceCourante = SessionCommission::create([
            'resume' => 'Séance de la campagne en cours.',
            'date_debut_session' => today()->subDays(14),
            'date_fin_session' => today()->subDays(10),
        ]);
        $seanceOuverte = SessionCommission::whereNull('date_fin_session')->orderBy('id_session')->first()
            ?? SessionCommission::create(['date_debut_session' => today()]);

        $dp = User::role('dp')->firstOrFail();
        $idAgent = Agent::where('user_id', User::role('gestionnaire-stock')->value('id'))->value('id_agent');
        $idSortie = TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE);

        foreach (self::DOSSIERS as $i => [$nom, $idTypeActeur, $periode, $etat, $localites, $lignes]) {
            $campagne = $periode === 'passee' ? $passee : $courante;
            $seance = $periode === 'passee' ? $seancePassee : $seanceCourante;

            // Soumis un mois avant la séance (ou il y a 3 semaines pour les dossiers encore en attente).
            $soumisLe = $etat === 'en_attente' || $etat === 'brouillon'
                ? now()->subDays(21 - $i)
                : $seance->date_debut_session->copy()->subDays(15)->setTime(9, 0);

            $acteur = Acteur::create([
                'nom' => self::MARQUE.$nom,
                'telephone' => '07'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
            ]);

            $demande = Demande::create([
                'id_acteur' => $acteur->id_acteur,
                'id_user' => $dp->id,
                'id_type_acteur' => $idTypeActeur,
                'id_campagne' => $campagne->id_campagne,
                'nom_structure' => self::MARQUE.$nom,
                'date_demande' => $soumisLe->toDateString(),
                'date_soummission' => $etat === 'brouillon' ? null : $soumisLe,
                // Une demande soumise a été visitée : on ne soumet pas sans visite de terrain.
                'terrain_visite' => $etat !== 'brouillon',
                'observations' => $etat !== 'brouillon' ? 'Visite de terrain effectuée : parcelle conforme.' : null,
                'motifs_rejet' => $etat === 'rejete' ? 'Stock indisponible pour la campagne.' : null,
                'id_session' => $etat === 'rejete' ? $seance->id_session : null,
            ]);
            $demande->localites()->sync($localites);

            foreach ($lignes as $idAppui => [$idType, $demandee]) {
                LigneDemande::create([
                    'id_demande' => $demande->id_demande,
                    'id_type_appui' => $idType,
                    'id_appui' => $idAppui,
                    'quantite_demandee' => $demandee,
                ]);
            }
            Traitement::creerTraitements($demande->id_demande, $demande->servicesConcernes());

            if (in_array($etat, ['retire', 'a_retirer', 'validee', 'seance_ouverte'], true)) {
                $session = $etat === 'seance_ouverte' ? $seanceOuverte : $seance;
                $creeLe = $etat === 'seance_ouverte' ? now()->subDays(2) : ($session->date_fin_session->copy()->setTime(16, 0));
                $retireLe = $etat === 'retire' ? $session->date_fin_session->copy()->addDays(5) : null;

                // « validee » : séance close, résultats pas encore publiés ; « seance_ouverte » : séance encore ouverte.
                $attribution = $this->attribuer($demande, $session, $lignes, $creeLe, $retireLe, ! in_array($etat, ['seance_ouverte', 'validee'], true));

                if ($retireLe) {
                    $this->destocker($attribution, $idAgent, $idSortie);
                }
            }
        }

        // Seuils d'alerte : de quoi voir le bloc « Alertes de stock » vivre.
        foreach ([1 => 80, 2 => 50, 3 => 3, 4 => 1] as $idAppui => $seuil) {
            Appui::whereKey($idAppui)->update(['seuil_alerte' => $seuil]);
        }
    }

    /** L'attribution et ses lignes ; publiée le lendemain de la clôture, sauf demande contraire. */
    private function attribuer(Demande $demande, SessionCommission $session, array $lignes, $creeLe, $retireLe, bool $publiee): Attribution
    {
        $attribution = (new Attribution)->forceFill([
            'id_demande' => $demande->id_demande,
            'id_session' => $session->id_session,
            'date_publication' => $publiee ? $creeLe->copy()->addDay() : null,
            'date_destockage' => $retireLe,
            'created_at' => $creeLe,
            'updated_at' => $creeLe,
        ]);
        $attribution->save();

        foreach ($lignes as $idAppui => [, , $attribuee]) {
            if ($attribuee !== null) {
                LigneAttribution::create([
                    'id_attribution' => $attribution->id_attribution,
                    'id_appui' => $idAppui,
                    'quantite_attribuee' => $attribuee,
                    'cout_attribution' => Appui::whereKey($idAppui)->value('cout_unitaire'),
                ]);
            }
        }

        return $attribution;
    }

    /** Même écriture que le retrait au magasin : un mouvement de sortie par ligne et le stock décrémenté. */
    private function destocker(Attribution $attribution, int $idAgent, int $idSortie): void
    {
        foreach ($attribution->lignesAttributions as $ligne) {
            Appui::whereKey($ligne->id_appui)->decrement('quantite_en_stock', $ligne->quantite_attribuee);

            MouvementStock::create([
                'id_type_mouvement_stock' => $idSortie,
                'id_agent' => $idAgent,
                'id_appui' => $ligne->id_appui,
                'id_attribution' => $attribution->id_attribution,
                'quantite' => $ligne->quantite_attribuee,
                'date_mouvement' => $attribution->date_destockage,
            ]);
        }
    }
}
