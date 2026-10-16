<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Services\ReglementService;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Services\ChronologieDemandeService;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Domain\Suivi\Services\StatistiqueService;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Le jeu de présentation doit rester cohérent : stock, mouvements, cycles, règlements, journal, idempotence. */
class DemonstrationSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemonstrationSeeder::class);
    }

    public function test_tous_les_statuts_sont_representes(): void
    {
        $admin = User::role('admin')->first();
        $this->actingAs($admin);

        $statuts = app(StatistiqueService::class)->demandesParStatut($admin);

        foreach (StatutDemandeE::cases() as $statut) {
            $this->assertGreaterThan(0, $statuts[$statut->value], $statut->libelle());
        }
    }

    public function test_le_stock_des_intrants_correspond_aux_entrees_et_sorties_et_reste_positif(): void
    {
        $entree = TypeMouvementStock::idDe(TypeMouvementStockEnum::ENTREE);
        $sortie = TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE);

        foreach ([1, 2, 5, 6, 7, 8] as $idAppui) {
            $initial = ['1' => 120, '2' => 120, '5' => 90, '6' => 70, '7' => 150, '8' => 400][(string) $idAppui];
            $entrees = (float) MouvementStock::where('id_appui', $idAppui)->where('id_type_mouvement_stock', $entree)->sum('quantite');
            $sorties = (float) MouvementStock::where('id_appui', $idAppui)->where('id_type_mouvement_stock', $sortie)->sum('quantite');

            $this->assertEqualsWithDelta($initial + $entrees - $sorties, (float) Appui::find($idAppui)->quantite_en_stock, 0.0001, "Appui #$idAppui");
            $this->assertGreaterThan(0, Appui::find($idAppui)->quantite_en_stock);
        }
    }

    public function test_chaque_retrait_a_ses_mouvements_et_inversement(): void
    {
        $sortie = TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE);
        $retirees = Attribution::whereNotNull('date_destockage')->get();
        $this->assertNotEmpty($retirees);

        foreach ($retirees as $attribution) {
            $this->assertEqualsWithDelta(
                (float) $attribution->lignesAttributions()->sum('quantite_attribuee'),
                (float) MouvementStock::where('id_attribution', $attribution->id_attribution)->where('id_type_mouvement_stock', $sortie)->sum('quantite'),
                0.0001,
            );
        }

        // Aucun mouvement orphelin : chacun vient d'un approvisionnement ou d'une attribution retirée.
        $this->assertSame(0, MouvementStock::whereNull('id_approvisionnement')->whereNotIn('id_attribution', $retirees->pluck('id_attribution'))->count());
    }

    public function test_les_equipements_des_cycles_clotures_sont_revenus(): void
    {
        foreach (Equipement::where('en_service', true)->get() as $equipement) {
            $derniereSortie = MouvementStock::where('id_equipement', $equipement->id_equipement)->latest('date_mouvement')->first();
            $this->assertFalse($derniereSortie->attribution->demande->campagne->estCloturee(), $equipement->matricule_equipement);
        }

        $this->assertGreaterThan(0, MouvementStock::where('id_type_mouvement_stock', TypeMouvementStock::idDe(TypeMouvementStockEnum::RETOUR))->count());
    }

    public function test_deux_annees_de_cycles_dont_seul_le_cycle_en_cours_est_ouvert(): void
    {
        $this->assertEquals(collect([2026, 2025]), Campagne::annees());
        $this->assertCount(3, Campagne::deLAnnee(2025)->get());
        $this->assertSame(['Cycle 3 (2026)'], Campagne::ouvertes()->get()->map->libelleComplet()->all());
    }

    public function test_un_acteur_qui_doit_encore_na_pas_de_demande_plus_recente(): void
    {
        $reglements = app(ReglementService::class);

        foreach ($reglements->attributionsImpayees() as $attribution) {
            $plusRecente = Demande::where('id_acteur', $attribution->demande->id_acteur)
                ->where('date_demande', '>', $attribution->demande->date_demande)
                ->exists();
            $this->assertFalse($plusRecente, "Acteur #{$attribution->demande->id_acteur}");
        }
        $this->assertGreaterThan(0, $reglements->acteursEnImpaye()->count());
    }

    public function test_degny_alfred_revient_en_individuel_apres_sa_cooperative(): void
    {
        $degny = Acteur::where('nom', 'Degny Alfred')->sole();
        $this->assertSame('freddegny@gmail.com', $degny->email);

        $demandes = $degny->demandes()->with('typeActeur')->whereNotNull('date_soummission')->orderBy('date_demande')->get();
        $this->assertSame('Coopérative du centre', $demandes->first()->nom_structure);
        $this->assertNull($demandes->last()->nom_structure);
        $this->assertTrue($demandes->last()->typeActeur->estIndividuel());
    }

    public function test_les_dossiers_ont_leur_chronologie(): void
    {
        $demande = Attribution::whereNotNull('date_destockage')->firstOrFail()->demande;
        $actions = app(ChronologieDemandeService::class)->pour($demande)->pluck('action')->all();

        foreach ([ActionJournalEnum::demandeSoumise, ActionJournalEnum::attributionCreee, ActionJournalEnum::attributionPubliee, ActionJournalEnum::attributionRetiree] as $action) {
            $this->assertContains($action->value, $actions);
        }
    }

    public function test_les_statistiques_et_le_dashboard_se_nourrissent_du_jeu(): void
    {
        $admin = User::role('admin')->first();

        $this->actingAs($admin)->get(route('statistiques.index'))
            ->assertOk()
            ->assertViewHas('indicateurs', fn ($i) => $i['attribuees'] > 0 && $i['delai_arbitrage'] !== null && $i['delai_retrait'] !== null);

        $this->get(route('dashboard'))->assertOk();

        $alertes = app(StatistiqueService::class)->stock()['alertes'];
        $this->assertNotEmpty($alertes);
    }

    public function test_le_jeu_ne_se_seme_quune_fois(): void
    {
        $avant = Demande::count();

        $this->seed(DemonstrationSeeder::class);

        $this->assertSame($avant, Demande::count());
    }
}
