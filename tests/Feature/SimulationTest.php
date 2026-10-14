<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Services\SimulationSortieService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** La simulation : un brouillon personnel, sans table dédiée, sans lien avec les séances. */
class SimulationTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $moi;
    private User $collegue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        $this->moi = User::role('agent-commission')->first();
        $this->collegue = User::factory()->create()->assignRole('agent-commission');
    }

    private function simulation(): SimulationSortieService
    {
        return app(SimulationSortieService::class);
    }

    /** L'état se calcule pour l'utilisateur connecté (les dossiers visibles dépendent de son rôle). */
    private function poste(User $user, int $idAppui): array
    {
        $this->actingAs($user);

        return $this->simulation()->etat($user)->firstWhere('id_appui', $idAppui);
    }

    private function quantiteSimulee(User $user, int $idAppui): int
    {
        return $this->poste($user, $idAppui)['simulee'];
    }

    public function test_le_brouillon_est_personnel(): void
    {
        $this->simulation()->noter($this->moi, 1, 40);
        $this->simulation()->noter($this->collegue, 1, 7);

        $this->assertSame(40, $this->quantiteSimulee($this->moi, 1));
        $this->assertSame(7, $this->quantiteSimulee($this->collegue, 1));

        // Et la simulation de l'un n'entame pas le disponible de l'autre.
        $poste = $this->poste($this->collegue, 1);
        $this->assertSame($poste['disponible'] - 7, $poste['restant']);
    }

    public function test_une_seule_quantite_par_appui_et_zero_retire_le_poste(): void
    {
        $this->simulation()->noter($this->moi, 1, 40);
        $this->simulation()->noter($this->moi, 1, 25);                  // la dernière saisie l'emporte

        $this->assertEquals([1 => 25], $this->simulation()->brouillon($this->moi));

        $this->simulation()->noter($this->moi, 1, 0);
        $this->assertSame([], $this->simulation()->brouillon($this->moi));
    }

    public function test_reinitialiser_ne_vide_que_son_propre_brouillon(): void
    {
        $this->simulation()->noter($this->moi, 1, 40);
        $this->simulation()->noter($this->collegue, 2, 10);

        $this->actingAs($this->moi)->delete(route('attributions.simulation.reinitialiser'))
            ->assertRedirect(route('attributions.simulation'));

        $this->assertSame(0, $this->quantiteSimulee($this->moi, 1));
        $this->assertSame(10, $this->quantiteSimulee($this->collegue, 2));
    }

    public function test_on_simule_sans_seance_ouverte(): void
    {
        SessionCommission::query()->delete();                           // plus aucune séance

        $this->actingAs($this->moi)
            ->postJson(route('attributions.simulation.noter'), ['id_appui' => 1, 'quantite' => 40])
            ->assertOk()->assertJson(['enregistre' => true]);

        $this->get(route('attributions.simulation'))->assertOk();
        $this->assertSame(40, $this->quantiteSimulee($this->moi, 1));
    }

    public function test_aucune_table_nest_dediee_au_brouillon(): void
    {
        $this->assertFalse(Schema::hasTable('simulations_sorties'));
    }

    public function test_une_saisie_invalide_est_refusee(): void
    {
        $this->actingAs($this->moi)
            ->postJson(route('attributions.simulation.noter'), ['id_appui' => 9999, 'quantite' => 5])->assertStatus(422);
        $this->postJson(route('attributions.simulation.noter'), ['id_appui' => 1, 'quantite' => -3])->assertStatus(422);

        $this->assertSame([], $this->simulation()->brouillon($this->moi));
    }

    public function test_plus_de_relecture_partagee(): void
    {
        $this->assertFalse(Route::has('attributions.simulation.etat'));

        $this->actingAs($this->moi)->get(route('attributions.simulation'))
            ->assertOk()
            ->assertSee('Brouillon personnel')
            ->assertDontSee('commun à toute la séance');
    }

    public function test_la_simulation_est_reservee_a_la_commission(): void
    {
        $this->actingAs(User::role('dp')->first())->get(route('attributions.simulation'))->assertForbidden();
        $this->postJson(route('attributions.simulation.noter'), ['id_appui' => 1, 'quantite' => 5])->assertForbidden();
    }
}
