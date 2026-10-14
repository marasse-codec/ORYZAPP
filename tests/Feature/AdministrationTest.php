<?php

namespace Tests\Feature;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\Localite;
use App\Domain\Referentiel\Models\Service;
use App\Domain\Stock\Models\Agent;
use App\Filament\Admin\Resources\LocaliteResource\Pages\ManageLocalites;
use App\Filament\Admin\Resources\ServiceResource\Pages\ManageServices;
use App\Filament\Admin\Resources\UserResource\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** L'administration : référentiels et comptes, réservés à l'admin ; rien de transactionnel. */
class AdministrationTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        $this->admin = User::role('admin')->first();
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_chaque_ecran_de_gestion_saffiche_pour_ladmin(): void
    {
        foreach (['campagnes', 'departements', 'localites', 'services', 'type-acteurs', 'type-appuis', 'type-pieces', 'type-systeme-productions', 'unite-dosages', 'users', 'notification-rules', 'journal-actions'] as $ressource) {
            $this->get(route("filament.admin.resources.$ressource.index"))->assertOk();
        }
    }

    public function test_le_panneau_est_reserve_a_ladmin(): void
    {
        foreach (['gestionnaire-stock', 'agent-commission', 'agent-service', 'dp'] as $role) {
            $this->actingAs(User::role($role)->first())->get('/administration')->assertForbidden();
        }
    }

    public function test_les_donnees_transactionnelles_ne_sont_plus_modifiables_a_la_main(): void
    {
        foreach (['demandes', 'attributions', 'mouvement-stocks', 'approvisionnements', 'equipements', 'appuis', 'session-commissions', 'lignes-demandes', 'lignes-attributions'] as $ressource) {
            $this->assertFalse(Route::has("filament.admin.resources.$ressource.index"), $ressource);
        }
    }

    // ------------------------------------------------------------ Référentiels

    public function test_on_cree_et_modifie_un_referentiel(): void
    {
        Livewire::test(ManageLocalites::class)
            ->callAction(CreateAction::class, ['lib_localite' => 'Daloa', 'id_departement' => 1])
            ->assertHasNoActionErrors();

        $localite = Localite::where('lib_localite', 'Daloa')->firstOrFail();

        Livewire::test(ManageLocalites::class)
            ->callTableAction(EditAction::class, $localite, ['lib_localite' => 'Daloa Centre', 'id_departement' => 1])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Daloa Centre', $localite->fresh()->lib_localite);
    }

    public function test_un_element_inutilise_se_supprime(): void
    {
        $localite = Localite::create(['lib_localite' => 'Temporaire', 'id_departement' => 1]);

        Livewire::test(ManageLocalites::class)->callTableAction(DeleteAction::class, $localite);

        $this->assertDatabaseMissing('localites', ['id_localite' => $localite->id_localite]);
    }

    public function test_un_element_utilise_ne_se_supprime_pas(): void
    {
        // La localité 1 est portée par une demande du jeu de base : la suppression en cascade effacerait ce lien.
        $this->assertTrue(Localite::find(1)->estUtilise());
        $liens = DB::table('demandes_localites')->where('id_localite', 1)->count();
        $this->assertGreaterThan(0, $liens);

        Livewire::test(ManageLocalites::class)
            ->callTableAction(DeleteAction::class, Localite::find(1))
            ->assertNotified('Suppression impossible : cet élément est utilisé ailleurs.');

        $this->assertDatabaseHas('localites', ['id_localite' => 1]);
        $this->assertSame($liens, DB::table('demandes_localites')->where('id_localite', 1)->count());

        // Même règle pour un service qui fournit des types d'appuis.
        Livewire::test(ManageServices::class)->callTableAction(DeleteAction::class, Service::find(1))
            ->assertNotified('Suppression impossible : cet élément est utilisé ailleurs.');
        $this->assertDatabaseHas('services', ['id_service' => 1]);
    }

    public function test_une_campagne_portant_des_demandes_est_utilisee(): void
    {
        $this->assertTrue(Campagne::where('lib_campagne', 'Cycle 1')->first()->estUtilise());     // la demande du jeu de base
        $this->assertFalse($this->campagne->estUtilise());                                         // la campagne de test est vide
    }

    // ------------------------------------------------------------------ Comptes

    public function test_la_creation_dun_agent_de_service_exige_son_service(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction(CreateAction::class, ['name' => 'Agent Test', 'email' => 'agent.test@example.com', 'password' => 'motdepasse1', 'role' => 'agent-service'])
            ->assertHasActionErrors(['id_service']);

        $this->assertDatabaseMissing('users', ['email' => 'agent.test@example.com']);

        Livewire::test(ManageUsers::class)
            ->callAction(CreateAction::class, ['name' => 'Agent Test', 'email' => 'agent.test@example.com', 'password' => 'motdepasse1', 'role' => 'agent-service', 'id_service' => 2])
            ->assertHasNoActionErrors();

        $user = User::where('email', 'agent.test@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('agent-service'));
        $this->assertSame(2, $user->id_service);
        $this->assertTrue(Hash::check('motdepasse1', $user->password));
        $this->assertNull($user->agent);                                  // pas un agent de stock
        $this->assertTrue(JournalAction::where('action', ActionJournalEnum::compteCree->value)->first()->cible->is($user));
    }

    public function test_un_gestionnaire_de_stock_recoit_son_agent(): void
    {
        Livewire::test(ManageUsers::class)
            ->callAction(CreateAction::class, ['name' => 'Magasinier Neuf', 'email' => 'magasin.neuf@example.com', 'password' => 'motdepasse1', 'role' => 'gestionnaire-stock'])
            ->assertHasNoActionErrors();

        $user = User::where('email', 'magasin.neuf@example.com')->firstOrFail();
        $this->assertNull($user->id_service);
        $this->assertNotNull($user->agent);                               // il peut signer des mouvements de stock
        $this->assertSame(1, Agent::where('user_id', $user->id)->count());
        $this->assertNotNull($user->idAgent());
    }

    public function test_changer_le_role_conserve_le_mot_de_passe_sil_nest_pas_fourni(): void
    {
        $dp = User::role('dp')->first();
        $ancien = $dp->password;

        Livewire::test(ManageUsers::class)
            ->callTableAction(EditAction::class, $dp, ['name' => $dp->name, 'email' => $dp->email, 'password' => '', 'role' => 'agent-commission'])
            ->assertHasNoTableActionErrors();

        $dp->refresh();
        $this->assertTrue($dp->hasRole('agent-commission'));
        $this->assertFalse($dp->hasRole('dp'));                           // un seul rôle
        $this->assertSame($ancien, $dp->password);
        $this->assertSame(1, JournalAction::where('action', ActionJournalEnum::compteModifie->value)->count());
    }

    public function test_un_compte_qui_a_agi_ne_se_supprime_pas_et_on_ne_se_supprime_pas_soi_meme(): void
    {
        $dp = User::role('dp')->first();                                  // porteur de demandes
        $this->assertTrue($dp->estUtilise());

        Livewire::test(ManageUsers::class)
            ->callTableAction(DeleteAction::class, $dp)
            ->assertNotified('Suppression impossible : cet élément est utilisé ailleurs.');
        $this->assertDatabaseHas('users', ['id' => $dp->id]);

        Livewire::test(ManageUsers::class)->assertTableActionHidden(DeleteAction::class, $this->admin);
    }
}
