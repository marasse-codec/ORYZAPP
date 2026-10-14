<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Les fiches d'arbitrage : des brouillons papier par demande à arbitrer, à remplir avant la saisie. */
class FichesArbitrageTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    public function test_la_fiche_reprend_la_demande_et_laisse_les_cadres_a_remplir(): void
    {
        $demande = $this->demandeSoumise();
        $this->systeme($demande, 1, 12.5);
        $this->ligne($demande, 1, 1, 30);

        $this->actingAs(User::role('agent-commission')->first())
            ->get(route('editions.arbitrages.imprimer', ['demandes' => [$demande->id_demande]]))
            ->assertOk()
            ->assertSee('DEM-'.str_pad($demande->id_demande, 3, '0', STR_PAD_LEFT))
            ->assertSee('Traitable (ha)')
            ->assertSee('Qté accordée')
            ->assertSee('12,50');
    }

    public function test_seules_les_demandes_a_arbitrer_ont_une_fiche(): void
    {
        $session = $this->seance();
        $attribuee = $this->demandeSoumise();
        $this->attribuer($attribuee, $session);
        $brouillon = $this->demande();
        $attendue = $this->demandeSoumise();

        $this->actingAs(User::role('agent-commission')->first());

        $this->get(route('editions.arbitrages.index'))
            ->assertOk()
            ->assertViewHas('demandes', fn ($d) => $d->pluck('id_demande')->contains($attendue->id_demande)
                && ! $d->pluck('id_demande')->contains($attribuee->id_demande)
                && ! $d->pluck('id_demande')->contains($brouillon->id_demande));

        // Cochée ou non, une demande déjà attribuée n'a plus de fiche.
        $this->get(route('editions.arbitrages.imprimer', ['demandes' => [$attribuee->id_demande]]))
            ->assertViewHas('demandes', fn ($d) => $d->isEmpty());

        // Sans sélection : toutes celles à arbitrer.
        $this->post(route('editions.arbitrages.imprimer'))
            ->assertViewHas('demandes', fn ($d) => $d->pluck('id_demande')->contains($attendue->id_demande));
    }

    public function test_seule_la_commission_les_tire(): void
    {
        $this->actingAs(User::role('dp')->first())->get(route('editions.arbitrages.index'))->assertForbidden();
        $this->get(route('editions.arbitrages.imprimer'))->assertForbidden();
    }
}
