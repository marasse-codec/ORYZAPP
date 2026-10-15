<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Services\AttributionService;
use App\Domain\Commission\Services\SeanceService;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Services\RestitutionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Les cas d'usage du domaine : leurs règles, et leur façon de répondre (message sur la page d'où l'on vient). */
class CasDUsageTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $commission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        $this->commission = User::role('agent-commission')->first();
        $this->actingAs($this->commission);
    }

    private function lignes(int $quantite = 5): array
    {
        return [['id_appui' => 1, 'quantite_attribuee' => $quantite]];
    }

    // ---------------------------------------------------------------- Attribution

    public function test_une_attribution_ne_se_saisit_que_dans_une_seance_ouverte(): void
    {
        $demande = $this->demandeSoumise();

        $this->post(route('attributions.store', $demande->id_demande), ['lignes' => $this->lignes()])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Aucune séance ouverte'));

        $close = $this->seance(cloturee: true);
        $this->post(route('attributions.store', $demande->id_demande).'?idSession='.$close->id_session, ['lignes' => $this->lignes()])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Aucune séance ouverte'));

        $this->assertSame(0, Attribution::where('id_demande', $demande->id_demande)->count());
    }

    public function test_une_demande_ne_recoit_quune_attribution(): void
    {
        $seance = $this->seance();
        $demande = $this->demandeSoumise();
        $service = app(AttributionService::class);

        $service->enregistrer($demande, $seance->id_session, $this->lignes(), 'Première décision');

        try {
            $service->enregistrer($demande, $seance->id_session, $this->lignes());
            $this->fail('Une seconde attribution aurait dû être refusée.');
        } catch (RegleMetierViolee $e) {
            $this->assertStringContainsString("n'attend pas d'arbitrage", $e->getMessage());
        }

        $this->assertSame(1, Attribution::where('id_demande', $demande->id_demande)->count());
        $this->assertSame('Première décision', Attribution::where('id_demande', $demande->id_demande)->value('commentaires'));
    }

    public function test_sans_cout_saisi_le_bon_fige_le_cout_du_catalogue(): void
    {
        $appui = Appui::findOrFail(1);
        $appui->update(['cout_unitaire' => 1200]);

        $attribution = app(AttributionService::class)->enregistrer($this->demandeSoumise(), $this->seance()->id_session, $this->lignes(3));

        // Le catalogue change ensuite : le bon garde le prix du jour de l'attribution.
        $appui->update(['cout_unitaire' => 9999]);

        $this->assertEquals(1200, $attribution->lignesAttributions()->first()->cout_attribution);
        $this->assertEquals(3600, $attribution->fresh()->montantTotal());
    }

    public function test_les_demandes_non_soumises_ou_rejetees_ne_sont_pas_attribuables(): void
    {
        $seance = $this->seance();
        $service = app(AttributionService::class);

        foreach ([$this->demande(), $this->demandeSoumise(['motifs_rejet' => 'Hors critères', 'id_session' => $seance->id_session])] as $demande) {
            try {
                $service->enregistrer($demande, $seance->id_session, $this->lignes());
                $this->fail("La demande #{$demande->id_demande} n'est pas attribuable.");
            } catch (RegleMetierViolee) {
                // attendu
            }
        }

        $this->assertSame(0, Attribution::count());
    }

    public function test_un_refus_ne_laisse_ni_attribution_ni_trace(): void
    {
        $demande = $this->demande();                                                  // non soumise
        $seance = $this->seance();
        $avant = [Attribution::count(), JournalAction::count()];

        $this->post(route('attributions.store', $demande->id_demande).'?idSession='.$seance->id_session, ['lignes' => $this->lignes()])
            ->assertSessionHas('error');

        $this->assertSame($avant, [Attribution::count(), JournalAction::count()]);
    }

    // --------------------------------------------------------------------- Séance

    public function test_une_seance_ne_se_cloture_quune_fois(): void
    {
        $seance = $this->seance();

        $this->post(route('commissions.cloturer', $seance->id_session))->assertRedirect(route('commissions.show', $seance->id_session));   // retour sur la séance clôturée
        $this->post(route('commissions.cloturer', $seance->id_session))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'déjà clôturée'));
    }

    public function test_on_ne_publie_pas_les_resultats_dune_seance_ouverte(): void
    {
        $seance = $this->seance();
        $attribution = $this->attribuer($this->demandeSoumise(), $seance, [1 => 5]);

        $this->post(route('attributions.publier'), ['id_session' => $seance->id_session])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'doit être clôturée'));

        $this->assertNull($attribution->fresh()->date_publication);
        $this->assertSame(0, app(SeanceService::class)->publier($this->seance(cloturee: true)));    // séance close sans attribution : rien à publier
    }

    // ---------------------------------------------------------------- Restitution

    public function test_la_restitution_est_tout_ou_rien_et_exige_un_equipement_en_service(): void
    {
        $this->actingAs(User::role('gestionnaire-stock')->first());

        $sorti = Equipement::where('id_appui', 3)->first();
        $this->sortirEquipement($sorti);
        $libre = Equipement::where('id_appui', 3)->where('en_service', false)->first();      // pas en service : irrestituable

        $this->post(route('equipements.retour.store'), ['retours' => [
            ['id_equipement' => $sorti->id_equipement, 'etat' => 'Bon état'],
            ['id_equipement' => $libre->id_equipement, 'etat' => 'Bon état'],
        ]])->assertSessionHas('error', fn ($m) => str_contains($m, 'n\'est pas en service'));

        // Le premier, restituable, n'a pas été restitué seul.
        $this->assertTrue($sorti->fresh()->en_service);
        $this->assertSame(0, JournalAction::count());

        // Un équipement « en service » sans sortie enregistrée est une anomalie : on refuse plutôt que d'inventer une origine.
        $orphelin = Equipement::where('id_appui', 3)->where('en_service', false)->first();
        $orphelin->update(['en_service' => true]);
        $this->post(route('equipements.retour.store'), ['retours' => [['id_equipement' => $orphelin->id_equipement, 'etat' => 'Bon état']]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'sans sortie enregistrée'));

        $this->assertSame(1, app(RestitutionService::class)->restituer([['id_equipement' => $sorti->id_equipement, 'etat' => 'Abîmé']], User::role('gestionnaire-stock')->first()));
    }
}
