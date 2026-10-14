<?php

namespace Tests\Feature;

use App\Domain\Notification\Notifications\SuiviDossierNotification;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** La stratégie de notifications : peu de messages, groupés, aux bons destinataires. */
class NotificationsTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
        Storage::fake('public');
    }

    private function magasinier(): User
    {
        return User::role('gestionnaire-stock')->first();
    }

    private function envoyees(User $user, ?string $contient = null): int
    {
        return Notification::sent($user, SuiviDossierNotification::class)
            ->filter(fn ($n) => $contient === null || str_contains($n->titre.' '.$n->message, $contient))
            ->count();
    }

    // ------------------------------------------------------------ Publication

    public function test_la_publication_envoie_un_message_par_dp_et_un_aux_magasiniers(): void
    {
        $dp1 = User::role('dp')->first();
        $dp2 = User::factory()->create()->assignRole('dp');
        $seance = $this->seance(cloturee: true);

        $this->attribuer($this->demandeSoumise(['id_user' => $dp1->id]), $seance, [1 => 5]);
        $this->attribuer($this->demandeSoumise(['id_user' => $dp1->id]), $seance, [1 => 5]);
        $this->demandeSoumise(['id_user' => $dp1->id, 'motifs_rejet' => 'Hors critères', 'id_session' => $seance->id_session]);
        $this->attribuer($this->demandeSoumise(['id_user' => $dp2->id]), $seance, [2 => 5]);

        $this->actingAs(User::role('agent-commission')->first())
            ->post(route('attributions.publier'), ['id_session' => $seance->id_session])->assertSessionHasNoErrors();

        // Un seul message par DP, avec le bilan de ses demandes.
        $this->assertSame(1, $this->envoyees($dp1, '2 de vos demandes attribuée(s), 1 rejetée(s)'));
        $this->assertSame(1, $this->envoyees($dp2, '1 de vos demandes attribuée(s), 0 rejetée(s)'));

        // Cloche + push + mail.
        Notification::assertSentTo($dp1, SuiviDossierNotification::class, fn ($n, $canaux) => $canaux === ['database', WebPushChannel::class, 'mail']);

        // Les magasiniers : un message, avec le nombre de bons à servir. Pas la commission ni les services.
        $this->assertSame(1, $this->envoyees($this->magasinier(), '3 bon(s) à servir'));
        $this->assertSame(0, Notification::sent(User::role('agent-commission')->first(), SuiviDossierNotification::class)->count());
    }

    public function test_republier_ou_publier_le_vide_naffiche_rien(): void
    {
        $dp = User::role('dp')->first();
        $seance = $this->seance(cloturee: true);
        $this->attribuer($this->demandeSoumise(['id_user' => $dp->id]), $seance, [1 => 5]);
        $agent = User::role('agent-commission')->first();

        $this->actingAs($agent)->post(route('attributions.publier'), ['id_session' => $seance->id_session]);
        $this->post(route('attributions.publier'), ['id_session' => $seance->id_session]);

        $this->assertSame(1, $this->envoyees($dp));                          // la seconde publication n'a rien à publier

        Notification::fake();
        $vide = $this->seance(cloturee: true);
        $this->post(route('attributions.publier'), ['id_session' => $vide->id_session]);
        Notification::assertNothingSent();
    }

    public function test_creation_et_soumission_ne_notifient_personne(): void
    {
        $this->actingAs(User::role('dp')->first());
        $demande = $this->demande(['terrain_visite' => true, 'observations' => 'Parcelle conforme']);
        $this->ligne($demande, 1, 1, 5);

        $this->post(route('demandes.soumettre', $demande->id_demande));

        Notification::assertNothingSent();
    }

    // ------------------------------------------------------------ Seuil de stock

    private function retirer($attribution, array $surcharge = [])
    {
        return $this->actingAs($this->magasinier())->patch(route('attributions.destocker', $attribution), $surcharge + [
            'date_destockage' => today()->toDateString(),
            'recipiendaire' => 'Kouassi Yao',
            'piece_identite' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
        ]);
    }

    private function aRetirer(array $lignes)
    {
        return $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), $lignes));
    }

    public function test_le_franchissement_du_seuil_previent_les_magasiniers_une_seule_fois(): void
    {
        Appui::find(1)->update(['quantite_en_stock' => 120, 'seuil_alerte' => 100]);

        $this->retirer($this->aRetirer([1 => 30]));                         // 120 -> 90 : franchit le seuil de 100
        $this->assertSame(1, $this->envoyees($this->magasinier(), 'Stock bas'));
        Notification::assertSentTo($this->magasinier(), SuiviDossierNotification::class,
            fn ($n, $canaux) => str_contains($n->message, 'Il reste 90') && $canaux === ['database', WebPushChannel::class, 'mail']);

        $this->retirer($this->aRetirer([1 => 10]));                         // 90 -> 80 : déjà sous le seuil, pas de répétition
        $this->assertSame(1, $this->envoyees($this->magasinier(), 'Stock bas'));
    }

    public function test_sans_seuil_ou_au_dessus_pas_dalerte(): void
    {
        Appui::find(1)->update(['quantite_en_stock' => 120, 'seuil_alerte' => 0]);          // seuil désactivé
        Appui::find(2)->update(['quantite_en_stock' => 120, 'seuil_alerte' => 50]);          // reste au-dessus

        $this->retirer($this->aRetirer([1 => 30, 2 => 20]));

        $this->assertSame(0, $this->envoyees($this->magasinier(), 'Stock bas'));
    }

    public function test_le_materiel_alerte_sur_les_equipements_disponibles(): void
    {
        Equipement::where('id_appui', 3)->delete();
        $ids = collect(['A', 'B', 'C'])->map(fn ($m) => Equipement::create(['matricule_equipement' => "CH-$m", 'etat' => 'bon', 'en_service' => false, 'id_appui' => 3])->id_equipement);
        Appui::find(3)->update(['seuil_alerte' => 2]);

        $attribution = $this->aRetirer([3 => 2]);
        $ligne = $attribution->lignesAttributions->first();
        $this->retirer($attribution, ['equipements' => [$ligne->id_ligne_attribution => [$ids[0], $ids[1]]]]);

        // 3 disponibles -> 1 : sous le seuil de 2.
        $this->assertSame(1, $this->envoyees($this->magasinier(), 'Il reste 1'));
    }

    public function test_une_sortie_refusee_nenvoie_aucune_alerte(): void
    {
        Appui::find(1)->update(['quantite_en_stock' => 120, 'seuil_alerte' => 100]);

        $this->retirer($this->aRetirer([1 => 30, 2 => 9999]))->assertSessionHas('error', fn ($m) => str_contains($m, 'Stock insuffisant'));   // la 2e ligne fait tout annuler

        $this->assertSame(0, $this->envoyees($this->magasinier()));
    }

    // ------------------------------------------------------------ Résumé quotidien

    public function test_le_resume_quotidien_ne_part_que_sil_y_a_quelque_chose_a_dire(): void
    {
        Artisan::call('notifications:resume-quotidien');
        Notification::assertNothingSent();

        // Une demande soumise cette nuit pour le service 1 (type d'appui 1) ; une plus ancienne, qui ne compte pas.
        $recente = $this->demandeSoumise(['date_soummission' => now()->subHours(3)]);
        $this->ligne($recente, 1, 1, 5);
        $ancienne = $this->demandeSoumise(['date_soummission' => now()->subDays(5)]);
        $this->ligne($ancienne, 3, 3, 1);                                   // type 3 : service 2

        Artisan::call('notifications:resume-quotidien');

        $service1 = User::where('email', 'agent.service1@example.com')->first();
        $service2 = User::where('email', 'agent.service2@example.com')->first();

        Notification::assertSentTo($service1, SuiviDossierNotification::class,
            fn ($n, $canaux) => str_contains($n->message, '1 nouvelle(s) demande(s)') && $canaux === ['database', 'mail']);   // cloche + mail, pas de push
        $this->assertSame(0, $this->envoyees($service2));                   // sa demande est trop ancienne

        // La commission reçoit ce qui attend son arbitrage.
        Notification::assertSentTo(User::role('agent-commission')->first(), SuiviDossierNotification::class,
            fn ($n) => str_contains($n->message, '2 demande(s) à arbitrer'));
        $this->assertSame(0, $this->envoyees(User::role('dp')->first()));
    }

    public function test_le_resume_est_planifie_chaque_matin_a_7_heures(): void
    {
        $evenement = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command, 'notifications:resume-quotidien'));

        $this->assertNotNull($evenement);
        $this->assertSame('0 7 * * *', $evenement->expression);
    }
}
