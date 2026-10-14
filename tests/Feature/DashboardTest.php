<?php

namespace Tests\Feature;

use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\Demande;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Suivi\Services\StatistiqueService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Le tableau de bord : affichage par rôle, cloisonnement, et chiffres exacts. */
class DashboardTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function stats(): StatistiqueService
    {
        return app(StatistiqueService::class);
    }

    public function test_chaque_role_obtient_son_tableau_de_bord(): void
    {
        foreach (User::all() as $user) {
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }
    }

    public function test_les_superficies_se_limitent_au_perimetre_de_lutilisateur(): void
    {
        $dp = User::role('dp')->first();
        $session = $this->seance(cloturee: true);

        $mienne = $this->demandeSoumise(['id_user' => $dp->id]);
        $this->systeme($mienne, 1, 10);
        $this->attribuer($mienne, $session, [], superficies: [1 => 7]);

        $autre = $this->demandeSoumise(['id_user' => User::role('admin')->value('id')]);
        $this->systeme($autre, 1, 20);
        $this->attribuer($autre, $session, [], superficies: [1 => 20]);

        // Le périmètre (scope pourUtilisateur) se lit sur l'utilisateur connecté.
        $this->actingAs($dp);
        $this->assertSame(['concernee' => 10.0, 'traitee' => 7.0, 'taux' => 70.0], $this->stats()->superficies($dp));

        $admin = User::role('admin')->first();
        $this->actingAs($admin);
        $this->assertSame(30.0, $this->stats()->superficies($admin)['concernee']);

        $this->actingAs($dp)->get(route('dashboard'))->assertOk()->assertSee('Superficie concernée');
    }

    public function test_les_blocs_dependent_du_role(): void
    {
        $this->actingAs(User::role('admin')->first())->get(route('dashboard'))
            ->assertSee('Alertes de stock')->assertSee('Séances ouvertes');

        $this->actingAs(User::role('gestionnaire-stock')->first())->get(route('dashboard'))
            ->assertSee('Alertes de stock')->assertDontSee('Séances ouvertes');

        $this->actingAs(User::role('agent-commission')->first())->get(route('dashboard'))
            ->assertSee('Séances ouvertes')->assertDontSee('Alertes de stock');

        $this->actingAs(User::role('dp')->first())->get(route('dashboard'))
            ->assertDontSee('Alertes de stock')->assertDontSee('Séances ouvertes')
            ->assertSee('Nouvelle demande');
    }

    public function test_le_dp_ne_compte_que_les_demandes_quil_a_emises(): void
    {
        $dp = User::role('dp')->first();
        $autreDp = User::factory()->create()->assignRole('dp');

        $this->demandeSoumise(['id_user' => $dp->id]);
        $this->demandeSoumise(['id_user' => $autreDp->id]);
        $this->demandeSoumise(['id_user' => $autreDp->id]);

        $this->actingAs($dp);
        $statuts = $this->stats()->demandesParStatut($dp);

        $this->assertSame(1, $statuts[StatutDemandeE::soumise->value]);
        // + la demande en brouillon du jeu de base, émise par ce DP
        $this->assertSame(2, array_sum($statuts));
    }

    public function test_lagent_de_service_ne_voit_que_les_demandes_soumises_de_son_service(): void
    {
        $agent = User::where('email', 'agent.service1@example.com')->first();   // service 1
        $this->assertSame(1, $agent->id_service);

        $demandeDuService = $this->demandeSoumise();
        $this->ligne($demandeDuService, 1);                                       // type 1 : service 1

        $autreService = $this->demandeSoumise();
        $this->ligne($autreService, 3);                                           // type 3 : service 2

        $brouillonDuService = $this->demande();
        $this->ligne($brouillonDuService, 1);                                     // pas soumise : invisible

        $this->actingAs($agent);
        $statuts = $this->stats()->demandesParStatut($agent);

        $this->assertSame(1, array_sum($statuts));
        $this->assertSame(1, $statuts[StatutDemandeE::soumise->value]);
    }

    public function test_le_statut_sql_et_le_statut_php_concordent(): void
    {
        $brouillon = $this->demande();
        $soumise = $this->demandeSoumise();
        $rejetee = $this->demandeSoumise(['motifs_rejet' => 'Dossier incomplet', 'id_session' => $this->seance()->id_session]);
        $attribuee = $this->demandeSoumise();
        $this->attribuer($attribuee, $this->seance());
        // Une attribution prime sur un rejet resté sur le dossier.
        $rejeteePuisAttribuee = $this->demandeSoumise(['motifs_rejet' => 'Ancien motif']);
        $this->attribuer($rejeteePuisAttribuee, $this->seance());

        $attendus = [
            $brouillon->id_demande => StatutDemandeE::brouillon,
            $soumise->id_demande => StatutDemandeE::soumise,
            $rejetee->id_demande => StatutDemandeE::rejetee,
            $attribuee->id_demande => StatutDemandeE::recu_attribution,
            $rejeteePuisAttribuee->id_demande => StatutDemandeE::recu_attribution,
        ];

        $sql = Demande::query()
            ->selectRaw('id_demande, '.Demande::statutSql().' AS code_statut')
            ->pluck('code_statut', 'id_demande');

        foreach ($attendus as $id => $statut) {
            $this->assertSame($statut, Demande::find($id)->statut, "PHP, demande #$id");
            $this->assertSame($statut->value, (int) $sql[$id], "SQL, demande #$id");
        }
    }

    public function test_les_dossiers_en_attente_ne_dependent_pas_du_role(): void
    {
        $this->demande();                                                   // brouillon : non
        $enAttente = $this->demandeSoumise();                               // à arbitrer
        $this->attribuer($this->demandeSoumise(), $this->seance(), [1 => 5]);  // déjà attribuée : non

        // L'admin voit tout par ailleurs, mais « en attente d'arbitrage » a un seul sens.
        $this->actingAs(User::role('admin')->first());

        $ids = app(\App\Domain\Commission\Services\SyntheseCommissionService::class)->dossiersEnAttente()->pluck('id_demande');

        $this->assertTrue($ids->contains($enAttente->id_demande));
        $this->assertSame(Demande::aArbitrer()->count(), $ids->count());
    }

    public function test_les_compteurs_de_la_commission(): void
    {
        $ouverte = $this->seance();
        $close = $this->seance(cloturee: true);

        $this->demandeSoumise();                                                 // à arbitrer
        $this->demandeSoumise(['motifs_rejet' => 'x', 'id_session' => $ouverte->id_session]); // rejetée : non
        $this->demande();                                                        // brouillon : non
        $this->attribuer($this->demandeSoumise(), $ouverte);                     // attribuée : non
        $this->attribuer($this->demandeSoumise(), $close);                       // séance close, non publiée : à publier

        $chiffres = $this->stats()->commission();

        // La séance du jeu de base est ouverte, plus la nôtre ; la séance close ne compte pas.
        $this->assertSame(2, $chiffres['sessions_ouvertes']);
        $this->assertSame(1, $chiffres['demandes_a_arbitrer']);
        $this->assertSame(1, $chiffres['attributions_a_publier']);
    }

    public function test_les_sorties_a_effectuer_ne_comptent_que_les_bons_publies_et_non_retires(): void
    {
        $ouverte = $this->seance();
        $close = $this->seance(cloturee: true);

        $this->attribuer($this->demandeSoumise(), $ouverte);                                                  // séance ouverte : non
        $this->attribuer($this->demandeSoumise(), $close);                                                    // validée mais non publiée : non
        $this->publier($this->attribuer($this->demandeSoumise(), $close, retireLe: today()->toDateString())); // déjà retirée : non
        $this->publier($this->attribuer($this->demandeSoumise(), $close));                                    // publiée, à retirer

        $this->assertSame(1, $this->stats()->stock()['sorties_a_effectuer']);
    }

    public function test_alertes_de_stock_pour_intrants_et_materiels(): void
    {
        // Intrant : le stock est la quantité en base.
        $intrantBas = Appui::find(1);
        $intrantBas->update(['quantite_en_stock' => 10, 'seuil_alerte' => 20]);
        // Intrant au-dessus de son seuil, et intrant sans seuil : pas d'alerte.
        Appui::find(2)->update(['quantite_en_stock' => 100, 'seuil_alerte' => 20]);
        $sansSeuil = Appui::find(4);
        $sansSeuil->update(['seuil_alerte' => 0]);

        // Matériel : le stock se compte en équipements disponibles (hors service = false).
        $charrue = Appui::find(3);
        $charrue->update(['seuil_alerte' => 2]);
        Equipement::where('id_appui', 3)->delete();       // le jeu de base en crée déjà : on repart de zéro
        Equipement::create(['matricule_equipement' => 'CH-001', 'etat' => 'bon', 'en_service' => false, 'id_appui' => 3]);
        Equipement::create(['matricule_equipement' => 'CH-002', 'etat' => 'bon', 'en_service' => true, 'id_appui' => 3]);

        $alertes = $this->stats()->stock()['alertes'];

        $this->assertEqualsCanonicalizing([1, 3], $alertes->pluck('appui.id_appui')->all());
        $this->assertSame(10, $alertes->firstWhere('appui.id_appui', 1)['stock']);
        $this->assertSame(1, $alertes->firstWhere('appui.id_appui', 3)['stock']);
        // Le plus bas stock d'abord.
        $this->assertSame(1, $alertes->first()['stock']);
    }
}
