<?php

namespace Tests\Feature;

use App\Domain\Acces\Enums\PermissionEnum;
use App\Domain\Acces\Services\CompteService;
use App\Domain\Acces\Services\DroitService;
use App\Domain\Administration\Enums\ParametreEnum;
use App\Domain\Administration\Services\ParametreService;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Filament\Admin\Pages\ParametresGeneraux;
use App\Filament\Admin\Pages\SanteSysteme;
use App\Filament\Admin\Resources\RoleResource\Pages\ManageRoles;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Tables\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Habilitations de l'administrateur : droits fins, paramètres, comptes désactivés, santé du système. */
class AdministrationAvanceeTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        $this->admin = User::role('admin')->firstOrFail();
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    // ------------------------------------------------------------ Droits fins

    public function test_ladmin_change_les_droits_dun_role_et_le_journal_le_note(): void
    {
        $dp = Role::findByName('dp');

        Livewire::test(ManageRoles::class)
            ->callTableAction(EditAction::class, $dp, ['droits' => [PermissionEnum::creerDemande->value, PermissionEnum::voirStocks->value]])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(User::role('dp')->first()->can(PermissionEnum::voirStocks->value));
        $this->assertFalse(User::role('dp')->first()->can(PermissionEnum::editDemande->value));

        $trace = JournalAction::where('action', ActionJournalEnum::droitsModifies->value)->sole();
        $this->assertSame([PermissionEnum::voirStocks->value], $trace->details['ajoutes']);
        $this->assertSame([PermissionEnum::editDemande->value], $trace->details['retires']);
    }

    public function test_le_role_administrateur_nest_pas_modifiable(): void
    {
        Livewire::test(ManageRoles::class)->assertTableActionHidden(EditAction::class, Role::findByName('admin'));

        $this->expectException(RegleMetierViolee::class);
        app(DroitService::class)->modifier(Role::findByName('admin'), []);
    }

    public function test_le_seeder_ne_defait_pas_les_reglages_de_ladmin(): void
    {
        app(DroitService::class)->modifier(Role::findByName('dp'), [PermissionEnum::creerDemande->value]);

        $this->seed(RolePermissionSeeder::class);   // comme à chaque déploiement

        $this->assertSame([PermissionEnum::creerDemande->value], Role::findByName('dp')->permissions->pluck('name')->all());
        $this->assertCount(count(PermissionEnum::cases()), Role::findByName('admin')->permissions);
    }

    // ------------------------------------------------------------- Paramètres

    public function test_les_parametres_se_modifient_dans_leurs_bornes_et_sont_journalises(): void
    {
        $parametres = app(ParametreService::class);
        $this->assertSame(8, $parametres->entier(ParametreEnum::motDePasseLongueurMin));   // valeur par défaut

        $parametres->modifier([ParametreEnum::motDePasseLongueurMin->value => 12, ParametreEnum::motDePasseSymboles->value => true]);

        $this->assertSame(12, $parametres->entier(ParametreEnum::motDePasseLongueurMin));
        $this->assertTrue($parametres->ouiNon(ParametreEnum::motDePasseSymboles));
        $trace = JournalAction::where('action', ActionJournalEnum::parametresModifies->value)->sole();
        $this->assertSame(['avant' => 8, 'apres' => 12], $trace->details[ParametreEnum::motDePasseLongueurMin->value]);

        $this->expectException(RegleMetierViolee::class);
        $parametres->modifier([ParametreEnum::motDePasseLongueurMin->value => 3]);
    }

    public function test_la_politique_de_mot_de_passe_suit_les_parametres(): void
    {
        $valide = fn (string $mdp) => Validator::make(['p' => $mdp], ['p' => Password::defaults()])->passes();

        $this->assertFalse($valide('motdepasse'));      // ni majuscule ni chiffre
        $this->assertTrue($valide('Motdepasse1'));

        app(ParametreService::class)->modifier([ParametreEnum::motDePasseLongueurMin->value => 14]);
        $this->assertFalse($valide('Motdepasse1'));     // trop court désormais
    }

    public function test_les_ecrans_parametres_et_sante_saffichent(): void
    {
        Livewire::test(ParametresGeneraux::class)
            ->assertSee('Longueur minimale du mot de passe')
            ->set('data.'.ParametreEnum::rappelBonsJours->value, 21)
            ->call('enregistrer')
            ->assertNotified('Paramètres enregistrés');
        $this->assertSame(21, app(ParametreService::class)->entier(ParametreEnum::rappelBonsJours));

        Livewire::test(SanteSysteme::class)->assertSee('Base de données')->assertSee('Sauvegardes')->assertSee('Tâches automatiques');
    }

    public function test_les_ecrans_dadministration_sont_reserves_a_ladmin(): void
    {
        $this->actingAs(User::role('directeur')->first());

        $this->get('/administration/parametres')->assertForbidden();
        $this->get('/administration/sante')->assertForbidden();
        $this->get('/administration/roles')->assertForbidden();
    }

    // ------------------------------------------------------- Comptes désactivés

    public function test_un_compte_desactive_ne_se_connecte_plus(): void
    {
        $dp = User::role('dp')->first();
        app(CompteService::class)->desactiver($dp, 'Fin de mission');
        auth()->logout();

        $this->post('/login', ['email' => $dp->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame('compte désactivé', JournalAction::where('action', ActionJournalEnum::connexionEchouee->value)->latest('id')->first()->details['motif']);
    }

    public function test_un_compte_desactive_pendant_sa_session_est_deconnecte(): void
    {
        $dp = User::role('dp')->first();
        $this->actingAs($dp)->get(route('dashboard'))->assertOk();

        $dp->update(['actif' => false]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_on_ne_desactive_ni_soi_meme_ni_le_dernier_administrateur(): void
    {
        $service = app(CompteService::class);

        try {
            $service->desactiver($this->admin);
            $this->fail('On ne doit pas pouvoir se désactiver soi-même.');
        } catch (RegleMetierViolee) {
            $this->assertTrue($this->admin->refresh()->estActif());
        }

        // Un autre administrateur tente de désactiver le dernier admin actif : refusé aussi.
        $autre = User::factory()->create();
        $autre->assignRole('admin');
        $service->desactiver($autre);                 // il reste $this->admin, actif : accepté
        $this->actingAs($autre->refresh());

        $this->expectException(RegleMetierViolee::class);
        $service->desactiver($this->admin);
    }

    public function test_fermer_son_compte_le_desactive_sans_le_supprimer(): void
    {
        $dp = User::role('dp')->first();

        $this->actingAs($dp)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $dp->id, 'actif' => false]);
    }
}
