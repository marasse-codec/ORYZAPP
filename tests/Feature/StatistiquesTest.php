<?php

namespace Tests\Feature;

use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\Localite;
use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Stock\Models\Appui;
use App\Domain\Suivi\Services\StatistiqueCampagneService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** La page de statistiques : accès réservé à l'admin, choix de la campagne, chiffres exacts. */
class StatistiquesTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function stats(): StatistiqueCampagneService
    {
        return app(StatistiqueCampagneService::class);
    }

    // ------------------------------------------------------------------ Accès

    public function test_un_visiteur_est_renvoye_a_la_connexion(): void
    {
        $this->get(route('statistiques.index'))->assertRedirect(route('login'));
    }

    public function test_seul_ladmin_accede_aux_statistiques(): void
    {
        foreach (['dp', 'agent-commission', 'gestionnaire-stock', 'agent-service'] as $role) {
            $this->actingAs(User::role($role)->first())->get(route('statistiques.index'))->assertForbidden();
        }

        $this->actingAs(User::role('admin')->first())->get(route('statistiques.index'))
            ->assertOk()
            ->assertSee('Campagne de test');
    }

    // --------------------------------------------------------------- Campagne

    public function test_la_campagne_en_cours_est_choisie_par_defaut_et_le_filtre_la_change(): void
    {
        $cycle2 = Campagne::where('lib_campagne', 'Cycle 2')->first();
        $this->demandeSoumise(['id_campagne' => $cycle2->id_campagne]);

        $this->actingAs(User::role('admin')->first())
            ->get(route('statistiques.index'))
            ->assertViewHas('campagne', fn ($c) => $c->is($this->campagne));

        $this->get(route('statistiques.index', ['campagne' => $cycle2->id_campagne]))
            ->assertViewHas('campagne', fn ($c) => $c->is($cycle2))
            ->assertViewHas('indicateurs', fn ($i) => $i['soumises'] === 1);

        $this->get(route('statistiques.index', ['campagne' => 9999]))->assertSessionHasErrors('campagne');
    }

    public function test_campagne_en_cours_selon_la_date(): void
    {
        Carbon::setTestNow('2026-05-15');
        $this->assertSame('Cycle 2', Campagne::enCours()->lib_campagne);

        // Deux campagnes qui se touchent (fin du cycle 1 = début du cycle 2) : la plus récente l'emporte.
        Carbon::setTestNow('2026-04-01');
        $this->assertSame('Cycle 2', Campagne::enCours()->lib_campagne);

        // Hors de toute campagne : la dernière ouverte.
        Carbon::setTestNow('2020-01-01');
        $this->assertSame($this->campagne->lib_campagne, Campagne::enCours()->lib_campagne);
    }

    public function test_une_campagne_vide_saffiche_sans_erreur(): void
    {
        $i = $this->stats()->indicateurs($this->campagne);

        $this->assertSame(0, $i['soumises']);
        $this->assertNull($i['taux_attribution']);
        $this->assertNull($i['delai_arbitrage']);
        $this->assertNull($i['delai_retrait']);
        $this->assertSame(0.0, $i['superficie_concernee']);
        $this->assertNull($i['taux_traitement']);

        $this->actingAs(User::role('admin')->first())->get(route('statistiques.index'))
            ->assertOk()->assertSee('Aucune demande soumise');
    }

    // --------------------------------------------------------------- Chiffres

    public function test_superficies_concernee_et_traitee(): void
    {
        $session = $this->seance(cloturee: true);

        $a = $this->demandeSoumise();
        $this->systeme($a, 1, 10);
        $this->systeme($a, 2, 5);
        $this->attribuer($a, $session, [], superficies: [1 => 6, 2 => 5]);

        $b = $this->demandeSoumise();
        $this->systeme($b, 1, 4);   // soumise, pas encore attribuée

        $brouillon = $this->demande();
        $this->systeme($brouillon, 3, 99);   // brouillon : ne compte pas

        $i = $this->stats()->indicateurs($this->campagne);
        $this->assertSame(19.0, $i['superficie_concernee']);
        $this->assertSame(11.0, $i['superficie_traitee']);
        $this->assertSame(57.9, $i['taux_traitement']);

        $this->assertSame(
            ['concernee' => 14.0, 'traitee' => 6.0],
            $this->stats()->superficiesParEcologie($this->campagne)->first(),
        );

        $this->actingAs(User::role('admin')->first())->get(route('statistiques.index'))
            ->assertOk()->assertSee('Superficie traitée');
    }

    public function test_indicateurs_et_delais(): void
    {
        $session = $this->seance(cloturee: true);

        // Deux dossiers du même acteur soumis le 1er, arbitrés 4 j et 2 j plus tard ; un seul retiré (1 j après).
        $a = $this->demandeSoumise(['date_soummission' => '2026-10-01 10:00:00']);
        $b = $this->demandeSoumise(['date_soummission' => '2026-10-01 10:00:00']);
        $this->attribuer($a, $session, creeLe: '2026-10-05 08:00:00', retireLe: '2026-10-06');
        $this->attribuer($b, $session, creeLe: '2026-10-03 17:00:00');

        $this->demandeSoumise();                                                                  // en attente
        $this->demandeSoumise(['motifs_rejet' => 'x', 'id_session' => $session->id_session]);    // rejetée
        $this->demande();                                                                         // brouillon : hors périmètre

        $i = $this->stats()->indicateurs($this->campagne);

        $this->assertSame(4, $i['soumises']);
        $this->assertSame(2, $i['attribuees']);
        $this->assertSame(1, $i['rejetees']);
        $this->assertSame(50.0, $i['taux_attribution']);
        $this->assertSame(1, $i['beneficiaires']);          // même acteur pour les deux dossiers
        $this->assertSame(3.0, $i['delai_arbitrage']);      // (4 + 2) / 2
        $this->assertSame(1.0, $i['delai_retrait']);        // un seul dossier retiré
    }

    public function test_les_demandes_dune_autre_campagne_ne_sont_pas_comptees(): void
    {
        $this->demandeSoumise(['id_campagne' => Campagne::where('lib_campagne', 'Cycle 1')->value('id_campagne')]);

        $this->assertSame(0, $this->stats()->indicateurs($this->campagne)['soumises']);
    }

    public function test_satisfaction_des_quantites_par_appui(): void
    {
        $session = $this->seance(cloturee: true);

        $a = $this->demandeSoumise();
        $this->ligne($a, 1, 1, 100);
        $b = $this->demandeSoumise();
        $this->ligne($b, 1, 1, 50);
        $c = $this->demandeSoumise();
        $this->ligne($c, 2, 2, 10);
        $this->ligne($c, 3, null, 5);                       // type seul, sans appui précis : non compté
        $brouillon = $this->demande();
        $this->ligne($brouillon, 1, 1, 999);                // brouillon : non compté

        $this->attribuer($a, $session, [1 => 60]);

        $lignes = $this->stats()->satisfactionParAppui($this->campagne)->keyBy('appui');

        $semences = $lignes[Appui::find(1)->libelle];
        $this->assertSame(150.0, $semences['demandee']);
        $this->assertSame(60.0, $semences['attribuee']);
        $this->assertSame(40.0, $semences['taux']);

        $uree = $lignes[Appui::find(2)->libelle];
        $this->assertSame(10.0, $uree['demandee']);
        $this->assertSame(0.0, $uree['taux']);

        $this->assertCount(2, $lignes);
        $this->assertSame(Appui::find(1)->libelle, $this->stats()->satisfactionParAppui($this->campagne)->first()['appui']); // tri : le plus demandé d'abord
    }

    public function test_repartition_par_localite(): void
    {
        $a = $this->demandeSoumise();
        $a->localites()->attach([1, 2]);
        $b = $this->demandeSoumise();
        $b->localites()->attach([1]);
        $this->demande()->localites()->attach([2]);          // brouillon : non compté

        $parLocalite = $this->stats()->parLocalite($this->campagne);

        $this->assertSame(2, $parLocalite[Localite::find(1)->lib_localite]);
        $this->assertSame(1, $parLocalite[Localite::find(2)->lib_localite]);
        $this->assertSame(Localite::find(1)->lib_localite, $parLocalite->keys()->first());
    }

    public function test_demandes_et_attributions_par_type_dappui(): void
    {
        $session = $this->seance(cloturee: true);

        $a = $this->demandeSoumise();
        $this->ligne($a, 1, 1, 10);
        $b = $this->demandeSoumise();
        $this->ligne($b, 1, 1, 10);
        $this->ligne($b, 2, 2, 10);
        $this->attribuer($a, $session, [1 => 10]);

        $types = $this->stats()->parTypeAppui($this->campagne);

        $this->assertSame(['demandees' => 2, 'servies' => 1], $types[TypeAppui::find(1)->lib_type_appui]);
        $this->assertSame(['demandees' => 1, 'servies' => 0], $types[TypeAppui::find(2)->lib_type_appui]);
    }

    public function test_beneficiaires_par_type_dacteur(): void
    {
        $session = $this->seance(cloturee: true);

        $this->attribuer($this->demandeSoumise(['id_type_acteur' => 1]), $session);
        $this->attribuer($this->demandeSoumise(['id_type_acteur' => 1]), $session);
        $this->attribuer($this->demandeSoumise(['id_type_acteur' => 2]), $session);
        $this->demandeSoumise(['id_type_acteur' => 2]);       // non attribuée : non comptée

        $types = $this->stats()->parTypeActeur($this->campagne);

        $this->assertSame(2, $types[TypeActeur::find(1)->lib_type_acteur]);
        $this->assertSame(1, $types[TypeActeur::find(2)->lib_type_acteur]);
    }
}
