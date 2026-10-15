<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Demande\Models\Rendement;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

class RendementTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $dp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->preparerDonnees();
        $this->dp = User::role('dp')->firstOrFail();
    }

    /** Une demande du DP de test, avec 4 ha de bas-fond et 2 ha d'irrigué, attribuée (et publiée si demandé). */
    private function attribution(bool $publiee = true): Attribution
    {
        $demande = $this->demandeSoumise(['id_user' => $this->dp->id]);
        $this->systeme($demande, 1, 4);
        $this->systeme($demande, 2, 2);
        $attribution = $this->attribuer($demande, $this->seance(cloturee: true), [1 => 5]);

        return $publiee ? $this->publier($attribution) : $attribution->refresh();
    }

    private function transmettre(Attribution $attribution, array $rendements, ?User $auteur = null)
    {
        return $this->actingAs($auteur ?? $this->dp)
            ->put(route('attributions.rendements.update', $attribution->id_attribution), ['rendements' => $rendements]);
    }

    public function test_le_rendement_se_calcule_en_tonnes_par_hectare(): void
    {
        $attribution = $this->attribution();
        $bas = $attribution->demande->systemesProductions->first();

        $this->transmettre($attribution, [$bas->id_systeme_production => ['superficie_recoltee' => '3', 'production_tonnes' => '9.6']])
            ->assertRedirect(route('attributions.show', $attribution->id_attribution));

        $this->assertSame(3.2, $bas->fresh()->rendement->rendement_t_ha);
    }

    public function test_un_systeme_laisse_vide_est_ignore_et_l_envoi_est_journalise(): void
    {
        $attribution = $this->attribution();
        [$bas, $irrigue] = $attribution->demande->systemesProductions->all();

        $this->transmettre($attribution, [
            $bas->id_systeme_production => ['superficie_recoltee' => '4', 'production_tonnes' => '10'],
            $irrigue->id_systeme_production => ['superficie_recoltee' => '', 'production_tonnes' => ''],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Rendement::count());

        $trace = JournalAction::where('action', ActionJournalEnum::rendementEnvoye->value)->sole();
        $this->assertSame($this->dp->id, $trace->user_id);
        $this->assertSame(1, $trace->details['superficies']);
        $this->assertEquals(10, $trace->details['production_tonnes']);
    }

    public function test_le_dp_peut_corriger_son_retour(): void
    {
        $attribution = $this->attribution();
        $bas = $attribution->demande->systemesProductions->first();
        $id = $bas->id_systeme_production;

        $this->transmettre($attribution, [$id => ['superficie_recoltee' => '4', 'production_tonnes' => '10']]);
        $this->transmettre($attribution, [$id => ['superficie_recoltee' => '4', 'production_tonnes' => '12']]);

        $this->assertSame(1, Rendement::count());
        $this->assertEquals(12, $bas->fresh()->rendement->production_tonnes);
    }

    public function test_pas_avant_la_publication(): void
    {
        $attribution = $this->attribution(publiee: false);
        $id = $attribution->demande->systemesProductions->first()->id_systeme_production;

        $this->transmettre($attribution, [$id => ['superficie_recoltee' => '1', 'production_tonnes' => '3']])
            ->assertSessionHas('error');

        $this->assertSame(0, Rendement::count());
    }

    public function test_la_superficie_recoltee_ne_depasse_pas_la_declaree(): void
    {
        $attribution = $this->attribution();
        $id = $attribution->demande->systemesProductions->first()->id_systeme_production;

        $this->transmettre($attribution, [$id => ['superficie_recoltee' => '5', 'production_tonnes' => '3']])
            ->assertSessionHas('error');

        $this->assertSame(0, Rendement::count());
    }

    public function test_superficie_et_production_vont_ensemble(): void
    {
        $attribution = $this->attribution();
        $id = $attribution->demande->systemesProductions->first()->id_systeme_production;

        $this->transmettre($attribution, [$id => ['superficie_recoltee' => '2', 'production_tonnes' => '']])
            ->assertSessionHasErrors("rendements.$id.production_tonnes");
    }

    public function test_une_superficie_etrangere_a_la_demande_est_refusee(): void
    {
        $attribution = $this->attribution();
        $autre = $this->systeme($this->demandeSoumise(['id_user' => $this->dp->id]), 3, 1);

        $this->transmettre($attribution, [$autre->id_systeme_production => ['superficie_recoltee' => '1', 'production_tonnes' => '3']])
            ->assertSessionHas('error');

        $this->assertSame(0, Rendement::count());
    }

    public function test_seuls_le_dp_de_la_demande_et_l_admin_transmettent(): void
    {
        $attribution = $this->attribution();
        $id = $attribution->demande->systemesProductions->first()->id_systeme_production;
        $donnees = [$id => ['superficie_recoltee' => '1', 'production_tonnes' => '3']];

        $this->transmettre($attribution, $donnees, User::role('gestionnaire-stock')->firstOrFail())->assertForbidden();
        $this->transmettre($attribution, $donnees, User::factory()->create())->assertForbidden();

        $this->assertSame(0, Rendement::count());
    }

    public function test_l_admin_peut_saisir_a_la_place_du_dp(): void
    {
        $attribution = $this->attribution();
        $id = $attribution->demande->systemesProductions->first()->id_systeme_production;
        $admin = User::role('admin')->firstOrFail();

        $this->transmettre($attribution, [$id => ['superficie_recoltee' => '2', 'production_tonnes' => '5']], $admin)
            ->assertSessionHasNoErrors();

        $this->assertSame($admin->id, Rendement::sole()->id_user);
        $this->actingAs($admin)->get(route('attributions.show', $attribution->id_attribution))
            ->assertSee('Transmettre les rendements');
    }

    public function test_la_liste_separe_les_retours_en_attente_des_retours_renseignes(): void
    {
        $enAttente = $this->attribution();
        $complete = $this->attribution();
        foreach ($complete->demande->systemesProductions as $systeme) {
            Rendement::create(['id_systeme_production' => $systeme->id_systeme_production, 'superficie_recoltee' => 1, 'production_tonnes' => 3]);
        }
        $nonPubliee = $this->attribution(publiee: false);

        $this->assertSame('attente', $enAttente->fresh()->etatRendement());
        $this->assertSame('renseigne', $complete->fresh()->etatRendement());
        $this->assertNull($nonPubliee->etatRendement());

        $groupes = $this->actingAs($this->dp)->get(route('attributions.index'))
            ->assertOk()
            ->assertSee('Retour de rendement en attente')
            ->assertSee('Retour de rendement renseigné')
            ->viewData('groupes');

        $this->assertSame([$enAttente->id_attribution], $groupes['attente']->pluck('id_attribution')->all());
        $this->assertSame([$complete->id_attribution], $groupes['renseigne']->pluck('id_attribution')->all());
    }

    public function test_les_statistiques_donnent_le_rendement_moyen_par_ecologie(): void
    {
        $a = $this->attribution();
        [$bas, $irrigue] = $a->demande->systemesProductions->all();
        Rendement::create(['id_systeme_production' => $bas->id_systeme_production, 'superficie_recoltee' => 2, 'production_tonnes' => 6]);
        Rendement::create(['id_systeme_production' => $irrigue->id_systeme_production, 'superficie_recoltee' => 2, 'production_tonnes' => 10]);

        $moyens = app(\App\Domain\Suivi\Services\StatistiqueCampagneService::class)->rendementsParEcologie($this->campagne);

        $this->assertSame(3.0, $moyens['Riziculture de bas-fond']['rendement']);
        $this->assertSame(5.0, $moyens['Riziculture irriguée']['rendement']);
        $this->assertNull($moyens['Riziculture de plateau']['rendement']);
        $this->assertSame(1, $moyens['Riziculture de bas-fond']['renseignees']);

        $this->actingAs(User::role('admin')->firstOrFail())->get(route('statistiques.index'))
            ->assertOk()
            ->assertSee('Rendement moyen par écologie');
    }

    public function test_la_fiche_propose_la_saisie_au_dp(): void
    {
        $attribution = $this->attribution();

        $this->actingAs($this->dp)->get(route('attributions.show', $attribution->id_attribution))
            ->assertOk()
            ->assertSee('Rendement des superficies')
            ->assertSee('Transmettre les rendements');
    }

    public function test_supprimer_la_superficie_emporte_son_retour(): void
    {
        $attribution = $this->attribution();
        $bas = $attribution->demande->systemesProductions->first();
        $this->transmettre($attribution, [$bas->id_systeme_production => ['superficie_recoltee' => '1', 'production_tonnes' => '3']]);

        $bas->delete();

        $this->assertSame(0, Rendement::count());
    }
}
