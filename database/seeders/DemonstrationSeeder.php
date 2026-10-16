<?php

namespace Database\Seeders;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Commission\Models\Paiement;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Models\SuperficieAttribution;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Demande\Models\Rendement;
use App\Domain\Demande\Models\SystemeProduction;
use App\Domain\Demande\Models\Traitement;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Agent;
use App\Domain\Stock\Models\Approvisionnement;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Jeu de présentation : deux années de vie (2025 et 2026, trois cycles chacune),
 * cohérent d'un bout à l'autre, pour montrer l'application.
 *
 *  - les cycles passés sont clôturés : leurs dossiers sont arbitrés, publiés, retirés,
 *    réglés, et les délégués de pôle ont transmis les rendements ;
 *  - le cycle en cours (2026, cycle 3) a des dossiers à chaque étape : brouillon, en
 *    attente d'arbitrage, en séance, validé, publié à retirer, retiré, rejeté, avec des
 *    règlements soumis, en brouillon ou manquants ;
 *  - le stock suit : approvisionnements en début de cycle, sorties aux retraits,
 *    équipements prêtés puis restitués ; il ne descend jamais sous zéro ;
 *  - le journal garde l'histoire de chaque dossier (chronologie des demandes).
 *
 * Exemple voulu : Degny Alfred a demandé en 2025 au nom de sa coopérative, et revient
 * en 2026 comme producteur individuel, sans structure.
 *
 * S'appuie sur le DatabaseSeeder (référentiels, comptes, cycles 2026). Tout s'écrit
 * dans une transaction ; les notifications sont coupées. Rejouable sans doublon.
 *
 * Usage : php artisan db:seed --class=DemonstrationSeeder
 * (appelé aussi par le DatabaseSeeder, sauf pendant les tests).
 */
class DemonstrationSeeder extends Seeder
{
    /** Téléphone du premier acteur de démonstration : sa présence dit que le jeu est déjà semé. */
    private const TEMOIN = '0501010101';

    /** Les demandeurs : [nom, date de naissance, téléphone]. L'indice 0 est l'acteur de base (Degny Alfred). */
    private const ACTEURS = [
        1 => ['Kouassi Yao Bernard', '1978-03-12', '0501010101'],
        2 => ['Koné Aminata', '1985-07-24', '0501010102'],
        3 => ['Traoré Moussa', '1972-11-02', '0501010103'],
        4 => ["N'Guessan Akissi Pélagie", '1990-01-15', '0501010104'],
        5 => ['Bamba Siaka', '1981-05-30', '0501010105'],
        6 => ['Ouattara Mariam', '1988-09-09', '0501010106'],
        7 => ['Yao Kouamé Jean', '1975-02-18', '0501010107'],
        8 => ['Coulibaly Fatou', '1993-12-01', '0501010108'],
        9 => ['Diallo Ibrahim', '1969-06-21', '0501010109'],
        10 => ['Kouadio Adjoua Rose', '1983-04-04', '0501010110'],
        11 => ['Konan Kouakou Paul', '1979-08-27', '0501010111'],
        12 => ['Soro Lacina', '1986-10-13', '0501010112'],
        13 => ['Touré Awa', '1991-03-08', '0501010113'],
        14 => ['Aka Brou Ernest', '1970-07-07', '0501010114'],
        15 => ['Dosso Karim', '1984-11-19', '0501010115'],
        16 => ['Ehui Marcelle', '1987-02-25', '0501010116'],
        17 => ['Kra Yao Simon', '1976-09-30', '0501010117'],
        18 => ['Tano Didier', '1982-12-12', '0501010118'],
    ];

    /** Les cycles 2025, créés et clôturés par la démonstration (ceux de 2026 viennent du CampagneSeeder). */
    private const CYCLES_2025 = [
        '2025-1' => ['Cycle 1', '2025-01-01', '2025-04-01'],
        '2025-2' => ['Cycle 2', '2025-04-01', '2025-08-01'],
        '2025-3' => ['Cycle 3', '2025-08-01', '2025-12-01'],
    ];

    /** Rendement de référence par écologie (t/ha) : bas-fond, irrigué, plateau. */
    private const RENDEMENT_ECOLOGIE = [1 => 4.2, 2 => 5.8, 3 => 2.3];

    /**
     * Les dossiers.
     * [acteur, type d'acteur, structure, cycle, état, localités, superficies [écologie => ha], appuis, règlement, rendement]
     *
     * États : retire, a_retirer, validee, seance_ouverte, en_attente, brouillon, rejete.
     * Règlement : solde (deux versements), comptant (un versement), partiel, brouillon (un versement soumis + un brouillon), aucun.
     * Rendement : coefficient appliqué au rendement de référence (null = pas encore transmis).
     * Types d'acteurs : 1 producteur individuel, 2 groupement informel, 3 coopérative, 4 entreprise agricole.
     */
    private const DOSSIERS = [
        // ---- 2025, cycle 1
        [1, 1, null, '2025-1', 'retire', [1], [1 => 3.0], [1, 7], 'solde', 0.95],
        [2, 3, 'Coopérative Ahouakro', '2025-1', 'retire', [1, 2], [1 => 12.0, 2 => 6.0], [5, 7, 8], 'solde', 1.0],
        [3, 2, 'Groupement Wognon', '2025-1', 'retire', [3], [2 => 8.0], [2, 10], 'comptant', 0.9],
        [4, 1, null, '2025-1', 'rejete', [4], [3 => 2.0], [6], 'aucun', null],
        // ---- 2025, cycle 2
        [5, 1, null, '2025-2', 'retire', [4], [3 => 4.0], [6, 7], 'comptant', 1.05],
        [6, 4, 'EARL Bandama', '2025-2', 'retire', [5], [2 => 15.0], [5, 7, 11], 'solde', 1.1],
        [7, 3, 'Coopérative Sassandra', '2025-2', 'retire', [2], [1 => 10.0], [1, 7, 8], 'solde', 0.98],
        [8, 1, null, '2025-2', 'rejete', [3], [1 => 1.5], [3], 'aucun', null],
        // ---- 2025, cycle 3 (Degny Alfred au nom de sa coopérative)
        [0, 3, 'Coopérative du centre', '2025-3', 'retire', [1, 2], [1 => 9.0, 2 => 4.0], [1, 7, 8], 'solde', 1.0],
        [9, 1, null, '2025-3', 'retire', [3], [2 => 5.0], [2, 7], 'comptant', 1.02],
        [10, 2, 'Groupement des femmes de Yabra', '2025-3', 'retire', [5], [3 => 6.0], [6, 8], 'solde', 0.92],
        [11, 1, null, '2025-3', 'retire', [4], [1 => 2.5], [5], 'comptant', 1.0],
        // ---- 2026, cycle 1
        [1, 1, null, '2026-1', 'retire', [1], [1 => 3.5], [1, 7, 8], 'comptant', 1.05],
        [12, 3, 'Coopérative Agboville Riz', '2026-1', 'retire', [3], [2 => 14.0], [5, 7, 10], 'solde', 1.12],
        [13, 1, null, '2026-1', 'retire', [2], [1 => 2.0], [6], 'comptant', 1.08],
        [14, 2, "Groupement d'Ehouéssi", '2026-1', 'rejete', [4], [3 => 3.0], [9], 'aucun', null],
        // ---- 2026, cycle 2
        [2, 3, 'Coopérative Ahouakro', '2026-2', 'retire', [1, 2], [1 => 13.0, 2 => 7.0], [5, 7, 8], 'solde', 1.1],
        [5, 1, null, '2026-2', 'retire', [4], [3 => 4.5], [6, 7], 'comptant', 1.06],
        [15, 1, null, '2026-2', 'retire', [5], [2 => 3.0], [2], 'comptant', 1.15],
        [6, 4, 'EARL Bandama', '2026-2', 'retire', [5], [2 => 16.0], [5, 7, 11], 'solde', 1.09],
        // ---- 2026, cycle 3 (en cours) : toutes les étapes
        [0, 1, null, '2026-3', 'en_attente', [1], [1 => 1.5], [1, 8], 'aucun', null],
        [3, 2, 'Groupement Wognon', '2026-3', 'retire', [3], [2 => 9.0], [2, 7], 'solde', null],
        [7, 3, 'Coopérative Sassandra', '2026-3', 'retire', [2], [1 => 11.0], [1, 7, 10], 'partiel', null],
        [9, 1, null, '2026-3', 'a_retirer', [3], [2 => 5.5], [2, 8], 'brouillon', null],
        [16, 1, null, '2026-3', 'a_retirer', [4], [3 => 3.0], [6], 'aucun', null],
        [12, 3, 'Coopérative Agboville Riz', '2026-3', 'validee', [3], [2 => 15.0], [5, 7], 'aucun', null],
        [17, 1, null, '2026-3', 'seance_ouverte', [5], [3 => 2.5], [6, 8], 'aucun', null],
        [10, 2, 'Groupement des femmes de Yabra', '2026-3', 'en_attente', [5], [3 => 7.0], [6, 7, 8], 'aucun', null],
        [18, 1, null, '2026-3', 'en_attente', [1], [1 => 2.0], [1], 'aucun', null],
        [11, 1, null, '2026-3', 'rejete', [4], [1 => 1.0], [4], 'aucun', null],
        [13, 1, null, '2026-3', 'brouillon', [2], [1 => 2.5], [6, 7], 'aucun', null],
        [4, 1, null, '2026-3', 'brouillon', [4], [3 => 2.0], [6], 'aucun', null],
    ];

    private const MOTIFS_REJET = [
        'Superficie déclarée non confirmée lors de la visite de terrain.',
        'Pièces justificatives incomplètes à la date de la séance.',
        "Stock insuffisant pour l'appui demandé sur ce cycle.",
    ];

    /** Les comptes qui signent les actions de démonstration (journal). */
    private array $auteurs = [];

    private int $idAgent;

    /** @var array<string, Campagne> */
    private array $cycles = [];

    /** @var array<string, array<string, SessionCommission>> */
    private array $seances = [];

    public function run(): void
    {
        if (Acteur::where('telephone', self::TEMOIN)->exists()) {
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
        $this->auteurs = [
            'dp' => User::role('dp')->firstOrFail(),
            'commission' => User::role('agent-commission')->firstOrFail(),
            'magasin' => User::role('gestionnaire-stock')->firstOrFail(),
            'comptable' => User::role('comptable')->first() ?? User::role('admin')->firstOrFail(),
            'admin' => User::role('admin')->firstOrFail(),
        ];
        $this->idAgent = Agent::where('user_id', $this->auteurs['magasin']->id)->value('id_agent');

        $this->preparerCycles();
        $acteurs = $this->creerActeurs();

        foreach (array_keys($this->cycles) as $cle) {
            $this->approvisionner($cle);
        }

        foreach (self::DOSSIERS as $i => $dossier) {
            $this->semerDossier($i, $dossier, $acteurs);
        }

        $this->restituerEquipementsDesCyclesPasses();

        // Seuils d'alerte : quelques appuis passent sous leur seuil, pour que le magasin ait de quoi réagir.
        foreach ([1 => 2, 2 => 2, 5 => 2, 6 => 75, 7 => 3, 8 => 50] as $idAppui => $seuil) {
            Appui::whereKey($idAppui)->update(['seuil_alerte' => $seuil]);
        }
    }

    // ------------------------------------------------------------------ Cycles et séances

    private function preparerCycles(): void
    {
        foreach (self::CYCLES_2025 as $cle => [$libelle, $debut, $fin]) {
            $this->cycles[$cle] = Campagne::create(['lib_campagne' => $libelle, 'mois_debut_campagne' => $debut, 'mois_fin_campagne' => $fin]);
        }

        foreach ([1, 2, 3] as $n) {
            $this->cycles["2026-{$n}"] = Campagne::whereYear('mois_debut_campagne', 2026)->where('lib_campagne', "Cycle {$n}")->firstOrFail();
        }

        // Une séance close par cycle passé, à six semaines de son début.
        foreach ($this->cycles as $cle => $cycle) {
            if ($cle === '2026-3') {
                continue;
            }

            $debut = $cycle->mois_debut_campagne->copy()->addDays(40);
            // La séance ouverte du jeu de base (mars 2026) devient celle du cycle 1 de 2026, désormais close.
            $base = $cle === '2026-1' ? SessionCommission::whereNull('date_fin_session')->orderBy('id_session')->first() : null;
            $this->seances[$cle]['close'] = $this->seance($debut, $debut->copy()->addDays(5), "Séance d'arbitrage du {$cycle->libelleComplet()}.", $base);
        }

        // Cycle en cours : une séance publiée, une séance close pas encore publiée, une séance ouverte.
        $this->seances['2026-3'] = [
            'close' => $this->seance(Carbon::parse('2026-09-18'), Carbon::parse('2026-09-23'), 'Première séance du Cycle 3 (2026) : résultats publiés.'),
            'validee' => $this->seance(Carbon::parse('2026-10-08'), Carbon::parse('2026-10-12'), 'Deuxième séance du Cycle 3 (2026) : résultats à publier.'),
            'ouverte' => $this->seance(today()->subDays(2), null, 'Séance en cours du Cycle 3 (2026).'),
        ];

        // Les cycles terminés sont clôturés par l'administrateur un mois après leur fin.
        foreach ($this->cycles as $cle => $cycle) {
            if ($cle !== '2026-3') {
                $cycle->update(['cloturee_le' => $cycle->mois_fin_campagne->copy()->addMonth(), 'id_user_cloture' => $this->auteurs['admin']->id]);
            }
        }
    }

    private function seance(Carbon $debut, ?Carbon $fin, string $resume, ?SessionCommission $existante = null): SessionCommission
    {
        $seance = $existante ?? new SessionCommission;
        $seance->fill(['resume' => $resume, 'date_debut_session' => $debut, 'date_fin_session' => $fin])->save();

        $this->tracer(ActionJournalEnum::sessionOuverte, $seance, 'commission', $debut->copy()->setTime(9, 0));
        if ($fin) {
            $this->tracer(ActionJournalEnum::sessionCloturee, $seance, 'commission', $fin->copy()->setTime(17, 0));
        }

        return $seance;
    }

    // ------------------------------------------------------------------ Acteurs et dossiers

    /** @return array<int, Acteur> */
    private function creerActeurs(): array
    {
        $acteurs = [0 => Acteur::where('telephone', '0758221948')->first() ?? Acteur::firstOrFail()];

        foreach (self::ACTEURS as $i => [$nom, $naissance, $telephone]) {
            $acteurs[$i] = Acteur::create(['nom' => $nom, 'date_naissance' => $naissance, 'telephone' => $telephone]);
        }

        return $acteurs;
    }

    private function semerDossier(int $i, array $dossier, array $acteurs): void
    {
        [$idActeur, $idTypeActeur, $structure, $cle, $etat, $localites, $superficies, $appuis, $reglement, $rendement] = $dossier;
        $cycle = $this->cycles[$cle];
        $seances = $this->seances[$cle];
        $seance = match ($etat) {
            'validee' => $seances['validee'],
            'seance_ouverte' => $seances['ouverte'],
            default => $seances['close'],
        };

        // Soumission deux à quatre semaines avant la séance ; les dossiers en attente, ces derniers jours.
        $soumisLe = match ($etat) {
            'en_attente' => today()->subDays(1 + $i % 5)->setTime(10, 0),
            'brouillon' => null,
            default => $seance->date_debut_session->copy()->subDays(12 + $i % 14)->setTime(10, 0),
        };
        $creeLe = ($soumisLe ?? today()->subDays(1 + $i % 3))->copy()->subDays(3)->setTime(9, 0);

        $demande = Demande::create([
            'id_acteur' => $acteurs[$idActeur]->id_acteur,
            'id_user' => $this->auteurs['dp']->id,
            'id_type_acteur' => $idTypeActeur,
            'id_campagne' => $cycle->id_campagne,
            'nom_structure' => $structure,
            'date_demande' => $creeLe->toDateString(),
            'periode_debut_mise_en_place' => $cycle->mois_debut_campagne->copy()->addMonth()->toDateString(),
            'periode_fin_mise_en_place' => $cycle->mois_debut_campagne->copy()->addMonths(4)->toDateString(),
            'date_soummission' => $soumisLe,
            // Une demande soumise a été visitée : on ne soumet pas sans visite de terrain.
            'terrain_visite' => $soumisLe !== null,
            'observations' => $soumisLe ? 'Visite de terrain effectuée : parcelles conformes à la déclaration.' : null,
            'motifs_rejet' => $etat === 'rejete' ? self::MOTIFS_REJET[$i % count(self::MOTIFS_REJET)] : null,
            'id_session' => $etat === 'rejete' ? $seance->id_session : null,
        ]);
        $demande->localites()->sync($localites);

        foreach ($superficies as $ecologie => $hectares) {
            SystemeProduction::create(['id_demande' => $demande->id_demande, 'id_type_systeme_production' => $ecologie, 'superficie' => $hectares]);
        }

        $total = array_sum($superficies);
        foreach ($appuis as $idAppui) {
            $appui = Appui::findOrFail($idAppui);
            LigneDemande::create([
                'id_demande' => $demande->id_demande,
                'id_type_appui' => $appui->id_type_appui,
                'id_appui' => $idAppui,
                'quantite_demandee' => $this->quantiteDemandee($appui, $total),
            ]);
        }
        Traitement::creerTraitements($demande->id_demande, $demande->servicesConcernes());

        $this->tracer(ActionJournalEnum::demandeCreee, $demande, 'dp', $creeLe);
        if ($soumisLe) {
            $this->tracer(ActionJournalEnum::demandeVisitee, $demande, 'dp', $soumisLe->copy()->subDay());
            $this->tracer(ActionJournalEnum::demandeSoumise, $demande, 'dp', $soumisLe);
        }

        if ($etat === 'rejete') {
            $this->tracer(ActionJournalEnum::demandeRejetee, $demande, 'commission', $seance->date_fin_session->copy()->setTime(15, 0), ['motif' => $demande->motifs_rejet]);
        }

        if (in_array($etat, ['retire', 'a_retirer', 'validee', 'seance_ouverte'], true)) {
            $this->attribuer($demande, $seance, $etat, $superficies, $reglement, $rendement);
        }
    }

    /** Ce que le demandeur sollicite : la dose à l'hectare, avec une marge (un équipement : une unité). */
    private function quantiteDemandee(Appui $appui, float $hectares): float
    {
        return $appui->typeAppui->est_intrant ? round($hectares * (float) $appui->dose_ha * 1.25, 2) : 1.0;
    }

    private function attribuer(Demande $demande, SessionCommission $seance, string $etat, array $superficies, string $reglement, ?float $rendement): void
    {
        $creeLe = $etat === 'seance_ouverte' ? now()->subDay() : $seance->date_fin_session->copy()->setTime(16, 0);
        $publieLe = in_array($etat, ['retire', 'a_retirer'], true) ? $creeLe->copy()->addDay()->setTime(9, 0) : null;
        $retireLe = $etat === 'retire' ? $publieLe->copy()->addDays(4 + $demande->id_demande % 9) : null;

        $attribution = (new Attribution)->forceFill([
            'id_demande' => $demande->id_demande,
            'id_session' => $seance->id_session,
            'commentaires' => 'Attribution conforme aux superficies visitées.',
            'date_publication' => $publieLe,
            'date_destockage' => $retireLe?->toDateString(),
            'recipiendaire' => $retireLe ? $demande->acteur->nom : null,
            'created_at' => $creeLe,
            'updated_at' => $creeLe,
        ]);
        $attribution->save();

        // La commission traite environ 85 % de la superficie déclarée, et attribue la dose correspondante.
        foreach ($superficies as $ecologie => $hectares) {
            SuperficieAttribution::create(['id_attribution' => $attribution->id_attribution, 'id_type_systeme_production' => $ecologie, 'superficie_traitee' => round($hectares * 0.85, 2)]);
        }
        $traitee = array_sum($superficies) * 0.85;

        foreach ($demande->lignes as $ligne) {
            $appui = $ligne->appui;
            LigneAttribution::create([
                'id_attribution' => $attribution->id_attribution,
                'id_appui' => $appui->id_appui,
                'quantite_attribuee' => $appui->typeAppui->est_intrant ? round($traitee * (float) $appui->dose_ha, 2) : 1,
                'cout_attribution' => $appui->cout_unitaire,
                'remise' => $appui->typeAppui->est_intrant ? 0 : 50,   // le matériel est cédé à moitié prix
            ]);
        }

        $this->tracer(ActionJournalEnum::attributionCreee, $attribution, 'commission', $creeLe, ['id_demande' => $demande->id_demande]);

        if ($publieLe) {
            $this->tracer(ActionJournalEnum::attributionPubliee, $attribution, 'commission', $publieLe, ['id_session' => $seance->id_session]);
            $this->regler($attribution->fresh(['lignesAttributions']), $reglement, $publieLe);
        }

        if ($retireLe) {
            $this->retirer($attribution->fresh(['lignesAttributions.appui.typeAppui']), $retireLe);
        }

        if ($rendement !== null) {
            $this->transmettreRendements($demande, $rendement);
        }
    }

    // ------------------------------------------------------------------ Règlements, retraits, rendements

    private function regler(Attribution $attribution, string $reglement, Carbon $publieLe): void
    {
        $montant = $attribution->montantTotal();

        $versements = match ($reglement) {
            'solde' => [[0.6, true, 6], [0.4, true, 25]],
            'comptant' => [[1.0, true, 3]],
            'partiel' => [[0.4, true, 7]],
            'brouillon' => [[0.5, true, 5], [0.5, false, 12]],
            default => [],
        };

        $deja = 0.0;
        foreach ($versements as $n => [$part, $soumis, $jours]) {
            $date = $publieLe->copy()->addDays($jours);
            if ($date->isFuture()) {
                $date = today()->subDays(1 + $n);
            }

            // Le dernier versement d'un solde règle exactement ce qui reste (pas d'écart d'arrondi).
            $valeur = $n === count($versements) - 1 && $reglement === 'solde' ? round($montant - $deja, 2) : round($montant * $part, 2);
            $deja += $valeur;

            $paiement = Paiement::create([
                'id_attribution' => $attribution->id_attribution,
                'montant' => $valeur,
                'date_paiement' => $date->toDateString(),
                'mode' => ['especes', 'mobile_money', 'virement'][($attribution->id_attribution + $n) % 3],
                'reference' => $n % 2 ? 'REC-'.$attribution->id_attribution.'-'.($n + 1) : null,
                'id_user' => $this->auteurs['comptable']->id,
                'date_soumission' => $soumis ? $date->copy()->setTime(14, 0) : null,
                'id_user_soumission' => $soumis ? $this->auteurs['comptable']->id : null,
            ]);

            $this->tracer($soumis ? ActionJournalEnum::paiementEnregistre : ActionJournalEnum::paiementBrouillon, $attribution, 'comptable', $date->copy()->setTime(14, 0), [
                'paiement' => $paiement->id_paiement,
                'montant' => $valeur,
            ]);
        }
    }

    /** Même écriture qu'un retrait au magasin : stock diminué, un mouvement de sortie par ligne, équipements en service. */
    private function retirer(Attribution $attribution, Carbon $retireLe): void
    {
        $idSortie = TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE);

        foreach ($attribution->lignesAttributions as $ligne) {
            $equipement = null;

            if (! $ligne->appui->typeAppui->est_intrant) {
                $equipement = Equipement::where('id_appui', $ligne->id_appui)->where('en_service', false)->orderBy('id_equipement')->first();

                if (! $equipement) {
                    continue; // plus d'unité disponible : la démo ne sort pas un équipement absent
                }
                $equipement->update(['en_service' => true]);
            }

            Appui::whereKey($ligne->id_appui)->decrement('quantite_en_stock', $ligne->quantite_attribuee);

            MouvementStock::create([
                'id_type_mouvement_stock' => $idSortie,
                'id_agent' => $this->idAgent,
                'id_appui' => $ligne->id_appui,
                'id_equipement' => $equipement?->id_equipement,
                'id_attribution' => $attribution->id_attribution,
                'quantite' => $ligne->quantite_attribuee,
                'date_mouvement' => $retireLe,
            ]);
        }

        $this->tracer(ActionJournalEnum::attributionRetiree, $attribution, 'magasin', $retireLe->copy()->setTime(11, 0), ['recipiendaire' => $attribution->recipiendaire]);
    }

    /** Les équipements prêtés sur un cycle terminé sont revenus au magasin dix jours avant la fin du cycle. */
    private function restituerEquipementsDesCyclesPasses(): void
    {
        $idSortie = TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE);
        $idRetour = TypeMouvementStock::idDe(TypeMouvementStockEnum::RETOUR);

        $sorties = MouvementStock::where('id_type_mouvement_stock', $idSortie)->whereNotNull('id_equipement')
            ->with('attribution.demande.campagne')->get();

        foreach ($sorties as $sortie) {
            $cycle = $sortie->attribution->demande->campagne;
            if (! $cycle->estCloturee()) {
                continue; // cycle en cours : l'équipement est encore chez le producteur
            }

            Equipement::whereKey($sortie->id_equipement)->update(['en_service' => false, 'etat' => 'bon']);
            Appui::whereKey($sortie->id_appui)->increment('quantite_en_stock');

            $retourLe = $cycle->mois_fin_campagne->copy()->subDays(10);
            MouvementStock::create([
                'id_type_mouvement_stock' => $idRetour,
                'id_agent' => $this->idAgent,
                'id_appui' => $sortie->id_appui,
                'id_equipement' => $sortie->id_equipement,
                'id_attribution' => $sortie->id_attribution,
                'quantite' => 1,
                'date_mouvement' => $retourLe,
            ]);

            $this->tracer(ActionJournalEnum::equipementRestitue, Equipement::find($sortie->id_equipement), 'magasin', $retourLe->copy()->setTime(10, 0), ['etat' => 'bon']);
        }
    }

    /** Le DP transmet ce que chaque superficie a produit, un peu après la récolte. */
    private function transmettreRendements(Demande $demande, float $coefficient): void
    {
        $production = 0.0;

        foreach ($demande->systemesProductions as $systeme) {
            $recoltee = round((float) $systeme->superficie * 0.95, 2);
            $tonnes = round($recoltee * self::RENDEMENT_ECOLOGIE[$systeme->id_type_systeme_production] * $coefficient, 3);
            $production += $tonnes;

            Rendement::create([
                'id_systeme_production' => $systeme->id_systeme_production,
                'superficie_recoltee' => $recoltee,
                'production_tonnes' => $tonnes,
                'id_user' => $this->auteurs['dp']->id,
            ]);
        }

        $date = $demande->campagne->mois_fin_campagne->copy()->addDays(15)->setTime(10, 0);
        $this->tracer(ActionJournalEnum::rendementEnvoye, $demande->attribution, 'dp', $date->isFuture() ? now() : $date, [
            'superficies' => $demande->systemesProductions->count(),
            'production_tonnes' => round($production, 3),
        ]);
    }

    // ------------------------------------------------------------------ Stock

    /**
     * Un approvisionnement en début de cycle : de quoi couvrir les retraits du cycle,
     * avec une marge. Une entrée de stock par intrant, comme au magasin.
     */
    private function approvisionner(string $cle): void
    {
        $besoins = [];
        foreach (self::DOSSIERS as [, , , $cycle, $etat, , $superficies, $appuis]) {
            if ($cycle !== $cle || ! in_array($etat, ['retire', 'a_retirer', 'validee', 'seance_ouverte'], true)) {
                continue;
            }
            foreach ($appuis as $idAppui) {
                $appui = Appui::find($idAppui);
                if ($appui->typeAppui->est_intrant) {
                    $besoins[$idAppui] = ($besoins[$idAppui] ?? 0) + array_sum($superficies) * 0.85 * (float) $appui->dose_ha;
                }
            }
        }

        if ($besoins === []) {
            return;
        }

        $date = $this->cycles[$cle]->mois_debut_campagne->copy()->addDays(10);
        $approvisionnement = Approvisionnement::create([
            'date_approvisionnement' => $date->toDateString(),
            'reference_commande' => 'BC-'.str_replace('-', '-C', $cle),
            'origine' => 'Programme national de soutien à la filière riz',
        ]);
        $idEntree = TypeMouvementStock::idDe(TypeMouvementStockEnum::ENTREE);

        foreach ($besoins as $idAppui => $besoin) {
            $quantite = ceil($besoin * 1.2 * 100) / 100;
            Appui::whereKey($idAppui)->increment('quantite_en_stock', $quantite);
            MouvementStock::create([
                'id_type_mouvement_stock' => $idEntree,
                'id_agent' => $this->idAgent,
                'id_appui' => $idAppui,
                'id_approvisionnement' => $approvisionnement->id_approvisionnement,
                'quantite' => $quantite,
                'date_mouvement' => $date,
            ]);
        }

        $this->tracer(ActionJournalEnum::approvisionnementEnregistre, $approvisionnement, 'magasin', $date->copy()->setTime(9, 0), [
            'reference_commande' => $approvisionnement->reference_commande,
            'lignes' => count($besoins),
        ]);
    }

    // ------------------------------------------------------------------ Journal

    /** Une ligne de journal datée, signée par l'un des comptes de démonstration. */
    private function tracer(ActionJournalEnum $action, Model $cible, string $auteur, Carbon $date, array $details = []): void
    {
        $trace = new JournalAction([
            'user_id' => $this->auteurs[$auteur]->id,
            'action' => $action->value,
            'cible_type' => $cible->getMorphClass(),
            'cible_id' => $cible->getKey(),
            'details' => $details ?: null,
        ]);
        $trace->created_at = $date;
        $trace->updated_at = $date;
        $trace->save();
    }
}
