<?php

namespace Tests\Feature;

use App\Domain\Commission\Enums\ModePaiementEnum;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Notification\Notifications\SuiviDossierNotification;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Les rappels du lundi : ils ne partent que s'il y a quelque chose qui traîne, et à qui peut agir. */
class RappelsProgrammesTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        // Le jeu de démonstration laisse une séance ouverte depuis longtemps : on repart d'un tableau net.
        DB::table('sessions_commissions')->update(['date_fin_session' => today()]);
    }

    private function recus(User $user, string $contient): int
    {
        return Notification::sent($user, SuiviDossierNotification::class)
            ->filter(fn ($n) => str_contains($n->titre, $contient))
            ->count();
    }

    private function bonPublie(int $ilYaJours): void
    {
        $attribution = $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 5]));
        DB::table('attributions')->where('id_attribution', $attribution->id_attribution)->update(['date_publication' => now()->subDays($ilYaJours)]);
    }

    public function test_sans_rien_qui_traine_rien_ne_part(): void
    {
        Artisan::call('notifications:rappels');

        Notification::assertNothingSent();
    }

    public function test_les_bons_non_retires_depuis_longtemps_sont_rappeles_aux_magasiniers(): void
    {
        $this->bonPublie(20);
        $this->bonPublie(3);                                                // trop récent

        Artisan::call('notifications:rappels');

        $magasinier = User::role('gestionnaire-stock')->first();
        Notification::assertSentTo($magasinier, SuiviDossierNotification::class,
            fn ($n, $canaux) => str_contains($n->message, '1 bon(s)') && $canaux === ['database', 'mail']);
        $this->assertSame(0, $this->recus(User::role('dp')->first(), 'Bons'));
    }

    public function test_les_seuils_se_reglent_en_ligne_de_commande(): void
    {
        $this->bonPublie(3);

        Artisan::call('notifications:rappels', ['--bons' => 2]);

        $this->assertSame(1, $this->recus(User::role('gestionnaire-stock')->first(), 'Bons'));
    }

    public function test_chaque_dp_est_rappele_de_ses_brouillons_anciens_seulement(): void
    {
        $dp = User::role('dp')->first();
        $autre = User::factory()->create()->assignRole('dp');

        $ancien = $this->demande(['id_user' => $dp->id]);
        $this->demande(['id_user' => $dp->id]);                             // récent : ne compte pas
        $this->demande(['id_user' => $autre->id]);                          // récent aussi
        DB::table('demandes')->where('id_demande', $ancien->id_demande)->update(['created_at' => now()->subDays(10)]);
        // Une demande soumise, même ancienne, n'est pas un brouillon.
        $soumise = $this->demandeSoumise(['id_user' => $autre->id]);
        DB::table('demandes')->where('id_demande', $soumise->id_demande)->update(['created_at' => now()->subDays(10)]);

        Artisan::call('notifications:rappels');

        Notification::assertSentTo($dp, SuiviDossierNotification::class, fn ($n) => str_contains($n->message, '1 demande(s) en brouillon'));
        $this->assertSame(0, $this->recus($autre, 'Brouillons'));
    }

    public function test_les_seances_ouvertes_depuis_trop_longtemps_sont_rappelees_a_la_commission(): void
    {
        $seance = $this->seance();
        $seance->forceFill(['date_debut_session' => today()->subDays(45)])->save();

        Artisan::call('notifications:rappels');

        Notification::assertSentTo(User::role('agent-commission')->first(), SuiviDossierNotification::class,
            fn ($n) => $n->titre === 'Séances à clôturer');
    }

    public function test_les_impayes_sont_rappeles_a_ladmin_avec_le_total_a_encaisser(): void
    {
        $acteur = Acteur::create(['nom' => 'Kouassi', 'telephone' => '0700000000', 'date_naissance' => '1980-01-01']);
        $attribution = $this->publier($this->attribuer($this->demandeSoumise(['id_acteur' => $acteur->id_acteur]), $this->seance(cloturee: true), [1 => 5]));
        LigneAttribution::where('id_attribution', $attribution->id_attribution)->update(['cout_attribution' => 2000]);
        $attribution->paiements()->create(['montant' => 4000, 'date_paiement' => today(), 'mode' => ModePaiementEnum::especes->value, 'date_soumission' => now()]);

        Artisan::call('notifications:rappels');

        Notification::assertSentTo(User::role('admin')->first(), SuiviDossierNotification::class,
            fn ($n, $canaux) => $n->titre === 'Règlements en attente'
                && str_contains($n->message, '1 acteur(s)')
                && str_contains($n->message, '6')   // 6 000 F restent à encaisser
                && $canaux === ['database', 'mail']);
        $this->assertSame(0, $this->recus(User::role('dp')->first(), 'Règlements'));

        // Une fois soldé, plus de rappel.
        $attribution->paiements()->create(['montant' => 6000, 'date_paiement' => today(), 'mode' => ModePaiementEnum::especes->value, 'date_soumission' => now()]);
        Notification::fake();
        Artisan::call('notifications:rappels');
        Notification::assertNothingSent();
    }

    public function test_les_rappels_sont_planifies_le_lundi_a_8_heures(): void
    {
        $evenement = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'notifications:rappels'));

        $this->assertNotNull($evenement);
        $this->assertSame('0 8 * * 1', $evenement->expression);
    }
}
