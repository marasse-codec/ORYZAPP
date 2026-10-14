<?php

namespace Tests\Feature;

use App\Domain\Commission\Services\SyntheseCommissionService;
use App\Domain\Stock\Models\Appui;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/**
 * Ce que la commission voit au moment d'arbitrer : pour chaque appui des types demandés,
 * la demande des dossiers en attente face au stock et à ce que les bons déjà prononcés ont engagé.
 */
class ConfrontationCommissionTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    public function test_la_confrontation_oppose_la_demande_au_stock_disponible(): void
    {
        $appui = Appui::findOrFail(1);
        $appui->update(['quantite_en_stock' => 100]);

        // Deux dossiers en attente demandent ce type d'appui ; un bon déjà prononcé et non retiré engage 30 unités.
        $this->ligne($this->demandeSoumise(), $appui->id_type_appui, null, 50);
        $this->ligne($this->demandeSoumise(), $appui->id_type_appui, null, 40);
        $this->attribuer($this->demandeSoumise(['id_session' => $this->seance()->id_session]), $this->seance(), [$appui->id_appui => 30]);

        $service = app(SyntheseCommissionService::class);
        $ligne = $service->confrontation($service->dossiersEnAttente())->firstWhere('id_appui', $appui->id_appui);

        $this->assertSame(2, $ligne['nb_dossiers']);
        $this->assertEquals(90, $ligne['demandee']);
        $this->assertEquals(100, $ligne['stock']);
        $this->assertEquals(30, $ligne['engagee']);
        $this->assertEquals(70, $ligne['disponible']);
        $this->assertFalse($ligne['couverte']);      // 90 demandés pour 70 disponibles
    }

    public function test_la_liste_des_demandes_affiche_la_confrontation_a_la_commission(): void
    {
        $appui = Appui::findOrFail(1);
        $this->ligne($this->demandeSoumise(), $appui->id_type_appui, null, 10);

        $this->actingAs(User::role('agent-commission')->first())
            ->get(route('demandes.index'))
            ->assertOk()
            ->assertSee('À traiter en commission')
            ->assertSee($appui->libelle);
    }

    public function test_sans_dossier_en_attente_il_ny_a_rien_a_confronter(): void
    {
        $service = app(SyntheseCommissionService::class);

        $this->assertTrue($service->confrontation($service->dossiersEnAttente())->isEmpty());
    }
}
