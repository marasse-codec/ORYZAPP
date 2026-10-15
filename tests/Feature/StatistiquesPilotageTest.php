<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Commission\Models\Paiement;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Suivi\Services\ComparaisonCampagnesService;
use App\Domain\Suivi\Services\StatistiqueCampagneService;
use App\Domain\Suivi\Support\FiltreStatistique;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Filtres, finances, comparaison entre campagnes et rapport PDF. */
class StatistiquesPilotageTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function stats(): StatistiqueCampagneService
    {
        return app(StatistiqueCampagneService::class);
    }

    // ---------------------------------------------------------------- Filtres

    public function test_les_filtres_restreignent_la_population(): void
    {
        $a = $this->demandeSoumise(['id_type_acteur' => 1]);
        $a->localites()->attach([1, 2]);                     // localités 1 et 2 : département 1
        $b = $this->demandeSoumise(['id_type_acteur' => 2, 'date_soummission' => now()->subDays(40)]);
        $b->localites()->attach([3]);                        // localité 3 : département 2
        $this->ligne($b, 2);

        $compter = fn (array $p) => $this->stats()->indicateurs($this->campagne, FiltreStatistique::depuis($p))['soumises'];

        $this->assertSame(2, $compter([]));
        $this->assertSame(1, $compter(['type_acteur' => 1]));
        $this->assertSame(1, $compter(['localite' => 2]));
        $this->assertSame(1, $compter(['departement' => 2]));
        $this->assertSame(1, $compter(['type_appui' => 2]));
        $this->assertSame(1, $compter(['du' => now()->subDays(20)->toDateString()]));       // $b est plus ancienne
        $this->assertSame(0, $compter(['type_acteur' => 1, 'localite' => 3]));               // les filtres se cumulent
    }

    public function test_une_demande_sur_deux_localites_du_filtre_nest_comptee_quune_fois(): void
    {
        $this->demandeSoumise()->localites()->attach([1, 2]);  // localités 1 et 2 : toutes deux du département 1

        $this->assertSame(1, $this->stats()->indicateurs($this->campagne, FiltreStatistique::depuis(['departement' => 1]))['soumises']);
    }

    public function test_la_page_accepte_les_filtres_et_refuse_un_filtre_inconnu(): void
    {
        $this->actingAs(User::role('admin')->first());

        $this->get(route('statistiques.index', ['type_acteur' => 1]))->assertOk()->assertSee('restreints par les filtres');
        $this->get(route('statistiques.index', ['localite' => 9999]))->assertSessionHasErrors('localite');
    }

    // --------------------------------------------------- Finances, annulations

    public function test_les_finances_suivent_le_du_et_lencaisse(): void
    {
        $session = $this->seance(cloturee: true);
        $publiee = $this->publier($this->attribuer($this->demandeSoumise(), $session, [1 => 4]));
        $nonPubliee = $this->attribuer($this->demandeSoumise(), $session, [1 => 1]);
        LigneAttribution::query()->update(['cout_attribution' => 1000, 'remise' => 0]);
        LigneAttribution::where('id_attribution', $publiee->id_attribution)->update(['remise' => 50]);   // 4 × 1000 × 50 % = 2 000
        Paiement::create(['id_attribution' => $publiee->id_attribution, 'montant' => 500, 'date_paiement' => today(), 'mode' => 'especes', 'date_soumission' => now()]);
        Paiement::create(['id_attribution' => $publiee->id_attribution, 'montant' => 300, 'date_paiement' => today(), 'mode' => 'especes']); // brouillon : non encaissé

        $finances = $this->stats()->finances($this->campagne);

        $this->assertSame(3000.0, $finances['engage']);        // 2 000 publiés + 1 000 non publiés
        $this->assertSame(2000.0, $finances['du']);
        $this->assertSame(500.0, $finances['encaisse']);
        $this->assertSame(1500.0, $finances['reste']);
        $this->assertSame(25.0, $finances['taux_recouvrement']);
        $this->assertNotNull($nonPubliee);
    }

    public function test_une_attribution_annulee_ne_compte_plus(): void
    {
        $attribution = $this->attribuer($this->demandeSoumise(), $this->seance(), [1 => 3]);
        $this->assertSame(1, $this->stats()->indicateurs($this->campagne)['attribuees']);

        $attribution->delete();                                   // suppression douce

        $this->assertSame(0, $this->stats()->indicateurs($this->campagne)['attribuees']);
        $this->assertSame(1, $this->stats()->parStatut($this->campagne)[StatutDemandeE::soumise->value]);
        $this->assertTrue($this->stats()->satisfactionParAppui($this->campagne)->isEmpty());
    }

    // -------------------------------------------------------------- Comparaison

    public function test_la_comparaison_range_les_campagnes_et_calcule_levolution(): void
    {
        $ancienne = Campagne::create(['lib_campagne' => 'Ancienne', 'mois_debut_campagne' => today()->subYear(), 'mois_fin_campagne' => today()->subYear()->addMonth()]);
        $this->demandeSoumise(['id_campagne' => $ancienne->id_campagne]);
        $this->demandeSoumise();
        $this->demandeSoumise();

        $lignes = app(ComparaisonCampagnesService::class)->comparer(collect([$this->campagne, $ancienne]));

        $this->assertSame(['Ancienne', 'Campagne de test'], $lignes->map(fn ($l) => $l['campagne']->lib_campagne)->all());
        $this->assertNull($lignes[0]['evolutions']['soumises']);  // pas de campagne avant la première
        $this->assertSame(100.0, $lignes[1]['evolutions']['soumises']);   // de 1 à 2 demandes : +100 %

        $this->actingAs(User::role('admin')->first())
            ->get(route('statistiques.comparaison', ['campagnes' => [$ancienne->id_campagne, $this->campagne->id_campagne]]))
            ->assertOk()->assertSee('Ancienne')->assertSee('+100,0 %');
    }

    // --------------------------------------------------------------- Rapports

    public function test_le_rapport_pdf_se_telecharge_et_laisse_une_trace(): void
    {
        $this->demandeSoumise();
        $this->actingAs(User::role('admin')->first());

        $this->get(route('statistiques.rapport', ['type_acteur' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $trace = JournalAction::where('action', ActionJournalEnum::exportTelecharge->value)->sole();
        $this->assertSame('rapport de campagne', $trace->details['document']);
        $this->assertSame(1, $trace->details['type_acteur']);
    }

    public function test_les_exports_excel_laissent_une_trace(): void
    {
        $this->actingAs(User::role('admin')->first())
            ->get(route('stats.export.demandes', ['date_debut' => today()->subMonth()->toDateString(), 'date_fin' => today()->toDateString()]))
            ->assertOk();

        $this->assertSame('stats.export.demandes', JournalAction::where('action', ActionJournalEnum::exportTelecharge->value)->sole()->details['document']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
