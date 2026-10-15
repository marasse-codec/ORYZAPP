<?php

namespace Tests\Feature;

use App\Domain\Acces\Services\CompteService;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Support\ReglesDemande;
use App\Domain\Notification\Notifications\SuiviDossierNotification;
use App\Domain\Notification\Services\AlerteService;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Suivi\Services\StatistiqueService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/**
 * Les responsabilités dans le circuit : le DP est responsable des demandes de ses départements,
 * le magasin ne voit que ses stocks, et le tableau de bord ne compte que les attributions publiées.
 * Plus les règles de saisie d'une demande (majorité, durée de mise en place).
 */
class CircuitResponsabilitesTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    /** Un DP responsable des départements donnés (créé par l'administration). */
    private function dpDe(array $departements, string $email): User
    {
        $this->actingAs(User::role('admin')->first());

        return app(CompteService::class)->creer([
            'name' => 'DP '.$email, 'email' => $email, 'password' => 'Motdepasse1',
            'role' => 'dp', 'departements' => $departements,
        ]);
    }

    // ------------------------------------------------------- DP responsables

    public function test_le_dp_voit_les_demandes_et_attributions_de_ses_departements(): void
    {
        $dpNord = $this->dpDe([1], 'nord@example.com');
        $dpSud = $this->dpDe([2], 'sud@example.com');

        // Localité 1 → département 1 ; localité 3 → département 2. Saisies par le DP de démonstration.
        $auNord = $this->demandeSoumise();
        $auNord->localites()->attach([1]);
        $auSud = $this->demandeSoumise();
        $auSud->localites()->attach([3]);
        $this->publier($this->attribuer($auNord, $this->seance(cloturee: true), [1 => 1]));

        $this->actingAs($dpNord)->get(route('demandes.show', $auNord->id_demande))->assertOk();
        $this->get(route('demandes.show', $auSud->id_demande))->assertNotFound();      // hors de ses départements
        $this->get(route('attributions.index'))->assertOk()->assertSee($auNord->acteur->nom);

        $this->actingAs($dpSud)->get(route('demandes.index'))
            ->assertOk()
            ->assertViewHas('demandes', fn ($d) => collect($d->items())->pluck('id_demande')->all() === [$auSud->id_demande]);
    }

    public function test_un_dp_doit_couvrir_au_moins_un_departement(): void
    {
        $this->actingAs(User::role('admin')->first());

        $this->expectException(RegleMetierViolee::class);
        app(CompteService::class)->creer(['name' => 'Sans', 'email' => 'sans@example.com', 'password' => 'Motdepasse1', 'role' => 'dp']);
    }

    public function test_les_dp_responsables_sont_notifies_de_la_publication(): void
    {
        $dpNord = $this->dpDe([1], 'nord@example.com');
        $dpSud = $this->dpDe([2], 'sud@example.com');
        $demande = $this->demandeSoumise();
        $demande->localites()->attach([1]);
        $session = $this->seance(cloturee: true);
        $this->publier($this->attribuer($demande, $session, [1 => 1]));

        Notification::fake();
        app(AlerteService::class)->resultatsPublies($session);

        Notification::assertSentTo($dpNord, SuiviDossierNotification::class);
        Notification::assertNotSentTo($dpSud, SuiviDossierNotification::class);
        // Celui qui a saisi la demande (DP de démonstration) est prévenu aussi.
        Notification::assertSentTo(User::where('email', 'dp@example.com')->first(), SuiviDossierNotification::class);
    }

    // --------------------------------------------------------- Tableaux de bord

    public function test_recu_attribution_ne_compte_que_les_attributions_publiees(): void
    {
        $admin = User::role('admin')->first();
        $this->actingAs($admin);
        $this->attribuer($this->demandeSoumise(), $this->seance(), [1 => 1]);                         // pas publiée
        $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 1]));

        $statuts = app(StatistiqueService::class)->demandesParStatut($admin);

        $this->assertSame(1, $statuts[StatutDemandeE::recu_attribution->value]);
        // L'attribution non publiée laisse sa demande « Soumise » au tableau de bord…
        $this->assertGreaterThanOrEqual(1, $statuts[StatutDemandeE::soumise->value]);
    }

    public function test_le_magasin_ne_voit_que_ses_stocks(): void
    {
        $this->actingAs(User::role('gestionnaire-stock')->first());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Derniers mouvements')
            ->assertDontSee('Répartition des demandes')
            ->assertDontSee('Dernières attributions');
        $this->get(route('demandes.index'))->assertForbidden();
        $this->get(route('attributions.index'))->assertForbidden();
        $this->get(route('demandes.create'))->assertForbidden();
    }

    // ------------------------------------------------------------- Séance

    public function test_apres_cloture_on_revient_sur_la_seance(): void
    {
        $session = $this->seance();
        $this->actingAs(User::role('agent-commission')->first())
            ->post(route('commissions.cloturer', $session->id_session))
            ->assertRedirect(route('commissions.show', $session->id_session));
    }

    // ------------------------------------------------------- Saisie d'une demande

    private function donnees(array $surcharge = []): array
    {
        return $surcharge + [
            'id_type_acteur' => 1, 'nom' => 'Koné', 'telephone' => '0711111111',
            'date_naissance' => '1990-01-01', 'localites' => [1],
            'id_campagne' => Campagne::enCours()->id_campagne,
            'periode_debut_mise_en_place' => '2026-11-15', 'periode_fin_mise_en_place' => '2027-02-15',
            'lignes' => [['id_type_appui' => 1, 'id_appui' => 1, 'quantite_demandee' => 1]],
        ];
    }

    public function test_le_demandeur_doit_avoir_18_ans_revolus(): void
    {
        $this->actingAs(User::role('dp')->first());

        $mineur = today()->subYears(18)->addDay()->toDateString();       // 18 ans demain
        $this->post(route('demandes.store'), $this->donnees(['date_naissance' => $mineur]))
            ->assertSessionHasErrors(['date_naissance' => ReglesDemande::messageMajorite()]);

        $majeur = today()->subYears(18)->toDateString();                  // 18 ans aujourd'hui
        $this->post(route('demandes.store'), $this->donnees(['date_naissance' => $majeur]))->assertSessionHasNoErrors();
    }

    public function test_la_mise_en_place_dure_au_moins_trois_mois(): void
    {
        $this->actingAs(User::role('dp')->first());

        $this->post(route('demandes.store'), $this->donnees(['periode_fin_mise_en_place' => '2027-02-14']))
            ->assertSessionHasErrors(['periode_fin_mise_en_place' => 'La fin de mise en place doit être au moins 3 mois après le début (au plus tôt le 15/02/2027).']);

        $this->post(route('demandes.store'), $this->donnees())->assertSessionHasNoErrors();
    }

    public function test_une_erreur_ramene_a_letape_du_champ(): void
    {
        $this->assertSame(1, ReglesDemande::etapeEnErreur(['telephone']));
        $this->assertSame(2, ReglesDemande::etapeEnErreur(['pieces.3']));
        $this->assertSame(3, ReglesDemande::etapeEnErreur(['lignes.0.id_type_appui', 'periode_fin_mise_en_place']));   // la première étape concernée
        $this->assertSame(4, ReglesDemande::etapeEnErreur(['lignes.0.id_type_appui']));

        // Le formulaire rouvert après un refus se place sur cette étape.
        $this->actingAs(User::role('dp')->first())
            ->from(route('demandes.create'))
            ->post(route('demandes.store'), $this->donnees(['periode_fin_mise_en_place' => '2026-11-20']));

        $this->get(route('demandes.create'))->assertOk()->assertSee('step: 3,', false);
    }
}
