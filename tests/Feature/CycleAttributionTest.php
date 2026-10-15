<?php

namespace Tests\Feature;

use App\Domain\Commission\Enums\StatutAttributionEnum;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Journal\Models\JournalAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/**
 * Cycle de vie d'une attribution : le statut se déduit de la séance et des dates.
 * La validation, c'est la clôture de la séance ; le bon s'imprime une fois les résultats publiés.
 */
class CycleAttributionTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function commission(): User
    {
        return tap(User::role('agent-commission')->first(), fn ($u) => $this->actingAs($u));
    }

    public function test_le_statut_se_deduit_de_la_seance_et_des_dates(): void
    {
        $ouverte = $this->seance();
        $close = $this->seance(cloturee: true);

        $enSeance = $this->attribuer($this->demandeSoumise(), $ouverte, [1 => 5]);
        $validee = $this->attribuer($this->demandeSoumise(), $close, [1 => 5]);
        $publiee = $this->publier($this->attribuer($this->demandeSoumise(), $close, [1 => 5]));
        $retiree = $this->publier($this->attribuer($this->demandeSoumise(), $close, [1 => 5], retireLe: today()->toDateString()));

        $attendus = [
            $enSeance->id_attribution => StatutAttributionEnum::enSeance,
            $validee->id_attribution => StatutAttributionEnum::validee,
            $publiee->id_attribution => StatutAttributionEnum::publiee,
            $retiree->id_attribution => StatutAttributionEnum::retiree,
        ];

        $sql = Attribution::query()
            ->selectRaw('id_attribution, '.Attribution::statutSql().' AS code_statut')
            ->pluck('code_statut', 'id_attribution');

        foreach ($attendus as $id => $statut) {
            $this->assertSame($statut, Attribution::find($id)->statut, "PHP, attribution #$id");
            $this->assertSame($statut->value, (int) $sql[$id], "SQL, attribution #$id");
        }
    }

    public function test_tant_que_la_seance_est_ouverte_lattribution_se_modifie_et_sannule(): void
    {
        $this->commission();
        $attribution = $this->attribuer($this->demandeSoumise(), $this->seance(), [1 => 5]);

        $this->get(route('attributions.edit', $attribution->id_attribution))->assertOk();
        $this->put(route('attributions.update', $attribution->id_attribution), [
            'commentaires' => 'Revue', 'lignes' => [['id_appui' => 1, 'quantite_attribuee' => 3]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(3.0, (float) $attribution->lignesAttributions()->first()->quantite_attribuee);

        // Annuler = suppression douce : l'attribution disparaît des écrans mais reste en base, avec ses lignes.
        $this->delete(route('attributions.destroy', $attribution->id_attribution));
        $this->assertSoftDeleted('attributions', ['id_attribution' => $attribution->id_attribution]);
        $this->assertDatabaseHas('lignes_attributions', ['id_attribution' => $attribution->id_attribution]);
        $this->assertNull($attribution->demande->fresh()->attribution);
    }

    public function test_les_superficies_traitees_se_creent_se_modifient_et_se_valident(): void
    {
        $this->commission();
        $demande = $this->demandeSoumise();
        $this->systeme($demande, 1, 12.5);
        $this->systeme($demande, 2, 8);
        $session = $this->seance();

        // Le formulaire propose la superficie demandée pour chaque écologie.
        $this->get(route('attributions.create', ['idDemande' => $demande->id_demande, 'idSession' => $session->id_session]))
            ->assertOk()
            ->assertViewHas('ecologies', fn ($e) => collect($e)->pluck('traitee', 'id_type')->all() === [1 => 12.5, 2 => 8.0]);

        $url = route('attributions.store', ['idDemande' => $demande->id_demande, 'idSession' => $session->id_session]);

        // Une écologie hors demande, ou au-delà du demandé : refus.
        $lignes = [['id_appui' => 1, 'quantite_attribuee' => 2]];
        $this->post($url, ['lignes' => $lignes, 'superficies' => [3 => 1]])->assertSessionHasErrors('superficies.3');
        $this->post($url, ['lignes' => $lignes, 'superficies' => [1 => 13]])->assertSessionHasErrors('superficies.1');

        $this->post($url, ['lignes' => $lignes, 'superficies' => [1 => 10, 2 => 0]])->assertSessionHasNoErrors();
        $attribution = Attribution::firstOrFail();
        $this->assertSame([1 => 10.0], $attribution->superficies->pluck('superficie_traitee', 'id_type_systeme_production')->map(fn ($v) => (float) $v)->all());

        // En modification, les valeurs enregistrées reviennent, puis sont remplacées.
        $this->get(route('attributions.edit', $attribution->id_attribution))
            ->assertViewHas('ecologies', fn ($e) => collect($e)->pluck('traitee', 'id_type')->all() === [1 => 10.0, 2 => 0.0]);
        $this->put(route('attributions.update', $attribution->id_attribution), [
            'lignes' => $lignes, 'superficies' => [2 => 4.5],
        ])->assertSessionHasNoErrors();
        $this->assertSame([2 => 4.5], $attribution->superficies()->pluck('superficie_traitee', 'id_type_systeme_production')->map(fn ($v) => (float) $v)->all());

        // Annuler l'attribution la retire des calculs, mais garde ses superficies pour l'historique.
        $this->delete(route('attributions.destroy', $attribution->id_attribution));
        $this->assertSoftDeleted('attributions', ['id_attribution' => $attribution->id_attribution]);
        $this->assertDatabaseCount('attributions_systemes_productions', 1);
    }

    public function test_la_cloture_valide_lattribution_qui_ne_bouge_plus(): void
    {
        $this->commission();
        $attribution = $this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 5]);
        $traces = JournalAction::count();

        $this->get(route('attributions.edit', $attribution->id_attribution))
            ->assertRedirect(route('attributions.show', $attribution->id_attribution));
        $this->put(route('attributions.update', $attribution->id_attribution), [
            'commentaires' => 'Trop tard', 'lignes' => [['id_appui' => 1, 'quantite_attribuee' => 1]],
        ])->assertRedirect()->assertSessionHas('error');
        $this->delete(route('attributions.destroy', $attribution->id_attribution))->assertSessionHas('error');

        $this->assertSame(5.0, (float) $attribution->lignesAttributions()->first()->quantite_attribuee);
        $this->assertDatabaseHas('attributions', ['id_attribution' => $attribution->id_attribution]);
        $this->assertSame($traces, JournalAction::count());     // un refus ne laisse pas de trace d'action
    }

    public function test_le_bon_ne_simprime_quapres_la_publication(): void
    {
        $this->commission();
        $attribution = $this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 5]);

        $this->get(route('attributions.show', $attribution->id_attribution))->assertOk()->assertDontSee('Imprimer le bon');
        $this->get(route('attributions.bon', $attribution->id_attribution))->assertForbidden();

        $this->publier($attribution);

        $this->get(route('attributions.show', $attribution->id_attribution))->assertSee('Imprimer le bon');
        $this->get(route('attributions.bon', $attribution->id_attribution))->assertOk();
    }

    public function test_les_etapes_de_proposition_nexistent_plus(): void
    {
        foreach (['attributions.soumettre', 'attributions.valider', 'attributions.annuler', 'attributions.createDirect', 'attributions.storeDirect'] as $route) {
            $this->assertFalse(Route::has($route), $route);
        }
    }

    public function test_seule_la_commission_modifie_et_publie(): void
    {
        $attribution = $this->attribuer($this->demandeSoumise(), $seance = $this->seance(), [1 => 5]);

        foreach (['dp', 'agent-service', 'gestionnaire-stock'] as $role) {
            $this->actingAs(User::role($role)->first());
            $this->get(route('attributions.edit', $attribution->id_attribution))->assertForbidden();
            $this->put(route('attributions.update', $attribution->id_attribution), ['lignes' => []])->assertForbidden();
            $this->post(route('attributions.publier'), ['id_session' => $seance->id_session])->assertForbidden();
        }
    }

    public function test_la_liste_de_lagent_de_service_est_en_lecture_seule(): void
    {
        $this->attribuer($this->demandeSoumise(), $this->seance(), [1 => 5]);      // appui 1 : service 1
        $agent = User::where('email', 'agent.service1@example.com')->first();

        $this->actingAs($agent)->get(route('attributions.index'))
            ->assertOk()
            ->assertSee('ATT-')
            ->assertSee('Lire')
            ->assertDontSee('Modifier')
            ->assertDontSee('Soumettre')
            ->assertDontSee('Annuler');
    }

    public function test_aucune_notification_a_la_creation_ni_a_la_soumission_dune_demande(): void
    {
        $this->actingAs(User::role('dp')->first());
        $demande = $this->demande(['terrain_visite' => true, 'observations' => 'Parcelle conforme']);
        $this->ligne($demande, 1, 1, 5);

        $this->post(route('demandes.soumettre', $demande->id_demande));

        $this->assertTrue($demande->fresh()->estSoumise());
        Notification::assertNothingSent();
    }
}
