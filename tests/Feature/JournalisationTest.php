<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Services\CreationDemandeService;
use App\Domain\Demande\Services\UpdateDemandeService;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Filament\Admin\Resources\JournalActionResource;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** SNF08 : chaque action métier laisse une trace signée, écrite avec l'action ou pas du tout. */
class JournalisationTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function trace(ActionJournalEnum $action): JournalAction
    {
        return JournalAction::where('action', $action->value)->sole();
    }

    // ---------------------------------------------------------------- Demandes

    public function test_creer_soumettre_et_modifier_une_demande(): void
    {
        $dp = User::role('dp')->first();
        $this->actingAs($dp);

        $demande = app(CreationDemandeService::class)->creer([
            'id_type_acteur' => 1, 'nom' => 'Kouassi', 'telephone' => '0700000000', 'date_naissance' => '1980-01-01',
            'localites' => [1], 'id_campagne' => $this->campagne->id_campagne,
            'periode_debut_mise_en_place' => '2026-11-01', 'periode_fin_mise_en_place' => '2027-02-01',
            'lignes' => [['id_type_appui' => 1, 'id_appui' => 1, 'quantite_demandee' => 5]],
        ]);
        $creee = $this->trace(ActionJournalEnum::demandeCreee);
        $this->assertSame($dp->id, $creee->user_id);
        $this->assertTrue($creee->cible->is($demande));
        $this->assertNotNull($creee->created_at);                 // horodatage

        app(UpdateDemandeService::class)->execute($demande, [
            'nom' => 'Kouassi', 'localites' => [1, 2],
            'lignes' => [['id_type_appui' => 1, 'id_appui' => 1, 'quantite_demandee' => 8]],
        ]);
        $this->assertTrue($this->trace(ActionJournalEnum::demandeModifiee)->cible->is($demande));

        // Pas de soumission sans visite de terrain.
        $this->post(route('demandes.visite.valider'), ['id_demande' => $demande->id_demande, 'observations' => 'Parcelle conforme']);
        $this->post(route('demandes.soumettre', $demande->id_demande));
        $this->assertTrue($this->trace(ActionJournalEnum::demandeSoumise)->cible->is($demande));
    }

    public function test_le_rejet_dune_demande_garde_son_motif(): void
    {
        $agent = User::role('agent-commission')->first();
        $this->actingAs($agent);
        $demande = $this->demandeSoumise();

        $this->patch(route('demandes.rejeter', ['id_session' => $this->seance()->id_session]), [
            'id_demande' => $demande->id_demande,
            'motifs_rejet' => 'Dossier incomplet',
        ]);

        $trace = $this->trace(ActionJournalEnum::demandeRejetee);
        $this->assertSame($agent->id, $trace->user_id);
        $this->assertTrue($trace->cible->is($demande));
        $this->assertSame('Dossier incomplet', $trace->details['motif']);
    }

    public function test_la_visite_de_terrain_est_journalisee(): void
    {
        $this->actingAs(User::role('dp')->first());
        $demande = $this->demandeSoumise();

        $this->post(route('demandes.visite.valider'), ['id_demande' => $demande->id_demande, 'observations' => 'Parcelle conforme']);

        $this->assertTrue($this->trace(ActionJournalEnum::demandeVisitee)->cible->is($demande));
    }

    // ------------------------------------------------------- Séances et attributions

    public function test_ouverture_et_cloture_dune_seance(): void
    {
        $agent = User::role('agent-commission')->first();
        $this->actingAs($agent);

        $this->post(route('commissions.store'), ['resume' => 'Séance test']);
        $session = SessionCommission::where('resume', 'Séance test')->firstOrFail();
        $this->assertTrue($this->trace(ActionJournalEnum::sessionOuverte)->cible->is($session));

        $this->post(route('commissions.cloturer', $session->id_session));
        $this->assertTrue($this->trace(ActionJournalEnum::sessionCloturee)->cible->is($session));
    }

    public function test_creation_modification_et_suppression_dune_attribution(): void
    {
        $agent = User::role('agent-commission')->first();
        $this->actingAs($agent);
        $demande = $this->demandeSoumise();
        $session = $this->seance();

        $this->post(route('attributions.store', $demande->id_demande).'?idSession='.$session->id_session, [
            'lignes' => [['id_appui' => 1, 'quantite_attribuee' => 10]],
        ])->assertSessionHasNoErrors();
        $attribution = Attribution::where('id_demande', $demande->id_demande)->firstOrFail();
        $this->assertTrue($this->trace(ActionJournalEnum::attributionCreee)->cible->is($attribution));

        $this->put(route('attributions.update', $attribution->id_attribution), [
            'commentaires' => 'Revue à la baisse',
            'lignes' => [['id_appui' => 1, 'quantite_attribuee' => 5]],
        ])->assertSessionHasNoErrors();
        $this->assertTrue($this->trace(ActionJournalEnum::attributionModifiee)->cible->is($attribution));

        $this->delete(route('attributions.destroy', $attribution->id_attribution));
        $suppression = $this->trace(ActionJournalEnum::attributionSupprimee);
        $this->assertSame($attribution->id_attribution, $suppression->cible_id);   // la cible est annulée, la trace reste
        $this->assertNull($suppression->cible);                                    // une attribution annulée n'est plus servie
        $this->assertSoftDeleted('attributions', ['id_attribution' => $attribution->id_attribution]);
        $this->assertArrayHasKey('commentaires', $suppression->details);           // copie du contenu annulé
    }

    public function test_la_publication_laisse_une_trace_par_attribution(): void
    {
        $agent = User::role('agent-commission')->first();
        $this->actingAs($agent);
        $session = $this->seance(cloturee: true);
        $a = $this->attribuer($this->demandeSoumise(), $session, [1 => 5]);
        $b = $this->attribuer($this->demandeSoumise(), $session, [1 => 5]);

        $this->post(route('attributions.publier'), ['id_session' => $session->id_session])->assertSessionHasNoErrors();

        $traces = JournalAction::where('action', ActionJournalEnum::attributionPubliee->value)->get();
        $this->assertEqualsCanonicalizing([$a->id_attribution, $b->id_attribution], $traces->pluck('cible_id')->all());
        $this->assertNotNull($a->fresh()->date_publication);
    }

    // ------------------------------------------------------------ Garanties du journal

    public function test_une_action_qui_echoue_ne_laisse_aucune_trace(): void
    {
        $this->actingAs(User::role('dp')->first());

        // Le type d'appui 999 n'existe pas : la création échoue après l'insertion de la demande.
        try {
            app(CreationDemandeService::class)->creer([
                'id_type_acteur' => 1, 'nom' => 'Fantôme', 'telephone' => '0700000001', 'date_naissance' => '1980-01-01',
                'localites' => [1], 'id_campagne' => $this->campagne->id_campagne,
                'periode_debut_mise_en_place' => '2026-11-01', 'periode_fin_mise_en_place' => '2027-02-01',
                'lignes' => [['id_type_appui' => 999, 'id_appui' => null, 'quantite_demandee' => 1]],
            ]);
        } catch (QueryException) {
        }

        $this->assertSame(0, JournalAction::count());
    }

    public function test_hors_session_rien_nest_ecrit(): void
    {
        $this->assertNull(JournalAction::enregistrer(ActionJournalEnum::demandeCreee, $this->demande()));
        $this->assertSame(0, JournalAction::count());
    }

    public function test_un_auteur_ne_peut_pas_etre_supprime(): void
    {
        $dp = User::role('dp')->first();
        $this->actingAs($dp);
        JournalAction::enregistrer(ActionJournalEnum::demandeCreee, $this->demande());

        $this->expectException(QueryException::class);
        $dp->delete();
    }

    public function test_le_journal_est_en_lecture_seule_pour_ladmin(): void
    {
        $this->actingAs(User::role('admin')->first());

        $this->assertTrue(JournalActionResource::canViewAny());
        $this->assertFalse(JournalActionResource::canCreate());
        $this->assertFalse(JournalActionResource::canEdit(new JournalAction));
        $this->assertFalse(JournalActionResource::canDelete(new JournalAction));
        $this->get(route('filament.admin.resources.journal-actions.index'))->assertOk();
    }

    public function test_le_journal_est_inaccessible_hors_administration(): void
    {
        foreach (['gestionnaire-stock', 'agent-commission', 'dp'] as $role) {
            $this->actingAs(User::role($role)->first());
            $this->assertFalse(JournalActionResource::canViewAny(), $role);
            $this->get(route('filament.admin.resources.journal-actions.index'))->assertForbidden();
        }
    }
}
