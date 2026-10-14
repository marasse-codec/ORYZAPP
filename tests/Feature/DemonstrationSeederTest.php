<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\Demande;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Suivi\Services\StatistiqueService;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Le jeu de démonstration doit rester cohérent : stock, mouvements, statuts, idempotence. */
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

    public function test_le_stock_des_intrants_correspond_aux_sorties_et_reste_positif(): void
    {
        foreach ([1, 2] as $idAppui) {
            $sorties = MouvementStock::where('id_appui', $idAppui)->sum('quantite');

            $this->assertEquals(120 - (float) $sorties, Appui::find($idAppui)->quantite_en_stock, "Appui #$idAppui");
            $this->assertGreaterThanOrEqual(0, Appui::find($idAppui)->quantite_en_stock);
        }
    }

    public function test_chaque_retrait_a_ses_mouvements_et_inversement(): void
    {
        $retirees = Attribution::whereNotNull('date_destockage')->get();
        $this->assertNotEmpty($retirees);

        foreach ($retirees as $attribution) {
            $this->assertSame(
                (float) $attribution->lignesAttributions()->sum('quantite_attribuee'),
                (float) MouvementStock::where('id_attribution', $attribution->id_attribution)->sum('quantite'),
            );
        }
        // Aucun mouvement orphelin : tous renvoient à une attribution retirée.
        $this->assertSame(
            0,
            MouvementStock::whereNotIn('id_attribution', $retirees->pluck('id_attribution'))->count(),
        );
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
