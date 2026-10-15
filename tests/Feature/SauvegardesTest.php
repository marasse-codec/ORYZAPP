<?php

namespace Tests\Feature;

use App\Domain\Shared\Services\LanceurArtisan;
use App\Domain\Suivi\Services\SauvegardeService;
use App\Filament\Admin\Pages\Sauvegardes;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Les sauvegardes : planification, configuration, écran d'administration, déclencheur externe. */
class SauvegardesTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        Storage::fake('sauvegardes');
        $this->dossier = config('backup.backup.name');
    }

    private function archive(string $nom, int $ilYaJours, string $contenu = 'zip'): void
    {
        $chemin = $this->dossier.'/'.$nom;
        Storage::disk('sauvegardes')->put($chemin, $contenu);
        touch(Storage::disk('sauvegardes')->path($chemin), now()->subDays($ilYaJours)->getTimestamp());
    }

    private function admin(): User
    {
        return User::role('admin')->first();
    }

    private function lanceurFactice(): object
    {
        $lanceur = new class extends LanceurArtisan {
            public array $commandes = [];

            public function lancer(string $commande): void
            {
                $this->commandes[] = $commande;
            }
        };
        $this->app->instance(LanceurArtisan::class, $lanceur);

        return $lanceur;
    }

    // ------------------------------------------------------- Planification et configuration

    public function test_la_sauvegarde_est_planifiee_chaque_nuit(): void
    {
        $evenements = collect(app(Schedule::class)->events());
        $expression = fn (string $commande) => $evenements->first(fn ($e) => str_contains($e->command, $commande))?->expression;

        $this->assertSame('0 2 * * *', $expression('backup:run'));
        $this->assertNull($expression('backup:clean'));       // pas de suppression automatique des archives
        $this->assertSame('0 3 * * *', $expression('backup:monitor'));
    }

    public function test_seuls_les_echecs_et_les_sauvegardes_perimees_sont_notifies(): void
    {
        $notifications = config('backup.notifications.notifications');

        $this->assertSame(['mail'], $notifications[BackupHasFailedNotification::class]);
        $this->assertSame([], $notifications[BackupWasSuccessfulNotification::class]);
    }

    public function test_la_retention_garde_les_sept_derniers_jours_puis_espace_les_archives(): void
    {
        $retention = config('backup.cleanup.default_strategy');

        $this->assertSame(7, $retention['keep_all_backups_for_days']);
        $this->assertSame(16, $retention['keep_daily_backups_for_days']);
        $this->assertSame(2, $retention['keep_yearly_backups_for_years']);
    }

    public function test_le_disque_de_destination_existe_et_la_base_sauvegardee_suit_la_connexion(): void
    {
        foreach (config('backup.backup.destination.disks') as $disque) {
            $this->assertNotNull(config("filesystems.disks.{$disque}"), "Le disque {$disque} n'est pas défini.");
        }

        $this->assertSame([config('database.default')], config('backup.backup.source.databases'));
    }

    public function test_une_archive_de_fichiers_se_cree_sur_le_disque_de_sauvegarde(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pieces/cni.pdf', 'contenu');

        Artisan::call('backup:run', ['--only-files' => true, '--disable-notifications' => true]);

        $archives = Storage::disk('sauvegardes')->files($this->dossier);
        $this->assertCount(1, $archives);
        $this->assertStringEndsWith('.zip', $archives[0]);
    }

    // --------------------------------------------------------------------- Service

    public function test_le_service_liste_les_archives_de_la_plus_recente_a_la_plus_ancienne(): void
    {
        $this->archive('2026-10-01-02-00-00.zip', 13);
        $this->archive('2026-10-13-02-00-00.zip', 1);
        $this->archive('notes.txt', 1);                                     // pas une archive

        $archives = app(SauvegardeService::class)->archives();

        $this->assertSame(['2026-10-13-02-00-00.zip', '2026-10-01-02-00-00.zip'], $archives->pluck('nom')->all());
    }

    public function test_letat_est_sain_avec_une_sauvegarde_de_moins_dun_jour(): void
    {
        $service = app(SauvegardeService::class);

        $this->assertFalse($service->etat()['saine']);                       // aucune archive
        $this->assertNull($service->etat()['derniere']);

        $this->archive('ancienne.zip', 3);
        $this->assertFalse($service->etat()['saine']);                       // trop ancienne

        $this->archive('recente.zip', 0);
        $etat = $service->etat();
        $this->assertTrue($etat['saine']);
        $this->assertSame(2, $etat['nombre']);
    }

    // ----------------------------------------------------------------- Administration

    public function test_ladmin_voit_les_archives_et_leur_etat(): void
    {
        $this->archive('2026-10-13-02-00-00.zip', 0);

        $this->actingAs($this->admin())
            ->get('/administration/sauvegardes')
            ->assertOk()
            ->assertSee('2026-10-13-02-00-00.zip')
            ->assertSee('À jour');
    }

    public function test_les_autres_roles_nont_pas_acces_aux_sauvegardes(): void
    {
        $this->archive('secrete.zip', 0);

        foreach (['dp', 'agent-commission', 'gestionnaire-stock'] as $role) {
            $this->actingAs(User::role($role)->first())->get('/administration/sauvegardes')->assertForbidden();
            $this->actingAs(User::role($role)->first())->get(route('sauvegardes.telecharger', ['disque' => 'sauvegardes', 'nom' => 'secrete.zip']))->assertForbidden();
        }
    }

    public function test_ladmin_telecharge_une_archive(): void
    {
        $this->archive('2026-10-13-02-00-00.zip', 0, 'CONTENU-ZIP');

        $reponse = $this->actingAs($this->admin())
            ->get(route('sauvegardes.telecharger', ['disque' => 'sauvegardes', 'nom' => '2026-10-13-02-00-00.zip']));

        $reponse->assertOk()->assertDownload('2026-10-13-02-00-00.zip');
    }

    public function test_on_ne_telecharge_que_ce_que_le_service_liste(): void
    {
        Storage::disk('sauvegardes')->put('ailleurs/secret.zip', 'x');         // hors du dossier des sauvegardes
        $this->actingAs($this->admin());

        $this->from('/administration/sauvegardes')
            ->get(route('sauvegardes.telecharger', ['disque' => 'sauvegardes', 'nom' => 'inconnue.zip']))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->get('/sauvegardes/sauvegardes/..%2F..%2F.env')->assertNotFound();
        $this->get(route('sauvegardes.telecharger', ['disque' => 'public', 'nom' => 'secret.zip']))->assertRedirect();
    }

    public function test_lancer_une_sauvegarde_la_confie_a_larriere_plan(): void
    {
        $lanceur = $this->lanceurFactice();
        $this->actingAs($this->admin());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Sauvegardes::class)->callAction('lancer')->assertHasNoActionErrors();

        $this->assertSame(['backup:run'], $lanceur->commandes);
    }

    // ---------------------------------------------------------- Déclencheur externe

    public function test_sans_jeton_configure_le_declencheur_nexiste_pas(): void
    {
        config(['services.cron.token' => null]);
        $lanceur = $this->lanceurFactice();

        $this->postJson(route('planification.executer'))->assertNotFound();
        $this->postJson(route('planification.executer'), [], ['Authorization' => 'Bearer '])->assertNotFound();
        $this->assertSame([], $lanceur->commandes);
    }

    public function test_le_declencheur_exige_le_bon_jeton(): void
    {
        config(['services.cron.token' => 'secret-de-test']);
        $lanceur = $this->lanceurFactice();

        $this->postJson(route('planification.executer'))->assertUnauthorized();
        $this->postJson(route('planification.executer'), [], ['Authorization' => 'Bearer mauvais'])->assertUnauthorized();
        $this->assertSame([], $lanceur->commandes);

        $this->postJson(route('planification.executer'), [], ['Authorization' => 'Bearer secret-de-test'])
            ->assertStatus(202)
            ->assertJson(['statut' => 'lancé']);
        $this->assertSame(['schedule:run'], $lanceur->commandes);
    }
}
