<?php

namespace Tests\Feature;

use App\Domain\Commission\Enums\ModePaiementEnum;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Commission\Models\Paiement;
use App\Domain\Commission\Services\ReglementService;
use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Services\CreationDemandeSerieService;
use App\Domain\Demande\Services\CreationDemandeService;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Le règlement des attributions, et le blocage des acteurs qui n'ont pas fini de payer. */
class ReglementTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private Acteur $acteur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        $this->acteur = Acteur::create(['nom' => 'Kouassi', 'telephone' => '0700000000', 'date_naissance' => '1980-01-01']);
    }

    /** Une attribution publiée de 10 000 F (5 × 2 000 F) pour l'acteur de test, ou pour $acteur. */
    private function attributionPubliee(?Acteur $acteur = null, bool $publiee = true): Attribution
    {
        $demande = $this->demandeSoumise(['id_acteur' => ($acteur ?? $this->acteur)->id_acteur]);
        $attribution = $this->attribuer($demande, $this->seance(cloturee: true), [1 => 5]);

        LigneAttribution::where('id_attribution', $attribution->id_attribution)->update(['cout_attribution' => 2000]);

        return $publiee ? $this->publier($attribution) : $attribution->refresh();
    }

    private function donneesDemande(array $surcharge = []): array
    {
        return $surcharge + [
            'id_type_acteur' => 1,
            'nom' => 'Kouassi',
            'telephone' => '0700000000',
            'date_naissance' => '1980-01-01',
            'localites' => [1],
            'id_campagne' => Campagne::enCours()->id_campagne,
            'periode_debut_mise_en_place' => '2026-11-01',
            'periode_fin_mise_en_place' => '2027-02-01',
            'lignes' => [['id_type_appui' => 1, 'id_appui' => 1, 'quantite_demandee' => 5]],
        ];
    }

    private function paiement(array $surcharge = []): array
    {
        return $surcharge + ['montant' => 4000, 'date_paiement' => today()->toDateString(), 'mode' => ModePaiementEnum::mobileMoney->value];
    }

    /** Un règlement déjà soumis, créé directement (pour préparer une situation). */
    private function paiementSoumis(array $surcharge = []): array
    {
        return $this->paiement($surcharge) + ['date_soumission' => now()];
    }

    /** Le parcours du comptable : saisie en brouillon, puis soumission. */
    private function regler(Attribution $attribution, array $donnees): Paiement
    {
        $service = app(ReglementService::class);

        return $service->soumettre($service->creerBrouillon($attribution, $donnees));
    }

    // ------------------------------------------------------------------ Reste dû

    public function test_le_reste_du_se_deduit_du_montant_et_des_paiements(): void
    {
        $attribution = $this->attributionPubliee();
        $this->assertSame(10000.0, $attribution->resteDu());

        // Un brouillon ne compte pas : seul un règlement soumis diminue le reste dû.
        $attribution->paiements()->create($this->paiement(['montant' => 1000]));
        $this->assertSame(10000.0, $attribution->refresh()->resteDu());
        $this->assertSame(1000.0, $attribution->totalBrouillons());

        $attribution->paiements()->create($this->paiementSoumis(['montant' => 4000]));

        $attribution->refresh();
        $this->assertSame(4000.0, $attribution->totalPaye());
        $this->assertSame(6000.0, $attribution->resteDu());
        $this->assertFalse($attribution->estReglee());
    }

    public function test_rien_nest_du_avant_la_publication(): void
    {
        $attribution = $this->attributionPubliee(publiee: false);

        $this->assertSame(0.0, $attribution->resteDu());
        $this->assertTrue($attribution->estReglee());
        $this->assertFalse(app(ReglementService::class)->estEnImpaye($this->acteur));
    }

    public function test_la_remise_de_la_commission_diminue_le_du(): void
    {
        $attribution = $this->attributionPubliee();
        LigneAttribution::where('id_attribution', $attribution->id_attribution)->update(['remise' => 20]);

        $this->assertSame(8000.0, $attribution->refresh()->resteDu());
    }

    // ------------------------------------------------------------ Encaissement

    public function test_un_reglement_partiel_puis_le_solde(): void
    {
        $service = app(ReglementService::class);
        $attribution = $this->attributionPubliee();
        $this->actingAs(User::role('admin')->first());

        $brouillon = $service->creerBrouillon($attribution, $this->paiement(['montant' => 4000]));
        $this->assertSame(10000.0, $attribution->refresh()->resteDu());     // brouillon : rien n'est encore payé
        $service->soumettre($brouillon);
        $this->assertTrue($service->estEnImpaye($this->acteur));

        $this->regler($attribution->refresh(), $this->paiement(['montant' => 6000]));
        $this->assertTrue($attribution->refresh()->estReglee());
        $this->assertFalse($service->estEnImpaye($this->acteur));
        $this->assertDatabaseHas('journals_actions', ['action' => ActionJournalEnum::paiementEnregistre->value, 'cible_id' => $attribution->id_attribution]);
    }

    public function test_un_reglement_ne_depasse_pas_le_reste_du(): void
    {
        $attribution = $this->attributionPubliee();
        $this->actingAs(User::role('admin')->first());

        $this->expectException(RegleMetierViolee::class);
        $this->expectExceptionMessage('ne pas dépasser le reste dû');

        try {
            app(ReglementService::class)->creerBrouillon($attribution, $this->paiement(['montant' => 10001]));
        } finally {
            $this->assertSame(0, $attribution->paiements()->count());
        }
    }

    public function test_pas_de_reglement_sur_une_attribution_non_publiee_ni_deja_soldee(): void
    {
        $service = app(ReglementService::class);
        $this->actingAs(User::role('admin')->first());

        try {
            $service->creerBrouillon($this->attributionPubliee(publiee: false), $this->paiement());
            $this->fail('Une attribution non publiée ne se règle pas.');
        } catch (RegleMetierViolee $e) {
            $this->assertStringContainsString('pas publiés', $e->getMessage());
        }

        $soldee = $this->attributionPubliee();
        $this->regler($soldee, $this->paiement(['montant' => 10000]));

        $this->expectException(RegleMetierViolee::class);
        $this->expectExceptionMessage('déjà soldée');
        $service->creerBrouillon($soldee->refresh(), $this->paiement(['montant' => 1]));
    }

    public function test_seul_qui_tient_la_caisse_enregistre_un_reglement(): void
    {
        $attribution = $this->attributionPubliee();
        $url = route('comptabilite.reglement.creer', $attribution->id_attribution);

        $this->actingAs(User::role('dp')->first())->post($url, $this->paiement())->assertForbidden();
        $this->actingAs(User::role('gestionnaire-stock')->first())->post($url, $this->paiement())->assertForbidden();
        $this->assertSame(0, $attribution->paiements()->count());

        $this->actingAs(User::role('comptable')->first())->post($url, $this->paiement())
            ->assertRedirect(route('comptabilite.reglement', $attribution->id_attribution))
            ->assertSessionHas('success');
        $this->assertSame(1, $attribution->paiements()->brouillons()->count());
    }

    public function test_un_brouillon_se_corrige_se_supprime_ou_se_soumet(): void
    {
        $attribution = $this->attributionPubliee();
        $this->actingAs(User::role('comptable')->first());

        $this->post(route('comptabilite.reglement.creer', $attribution), $this->paiement(['montant' => 3000]));
        $brouillon = $attribution->paiements()->sole();

        $this->put(route('comptabilite.reglement.modifier', $brouillon), $this->paiement(['montant' => 3500]))->assertSessionHas('success');
        $this->assertSame(3500.0, (float) $brouillon->refresh()->montant);
        $ecart = JournalAction::where('action', ActionJournalEnum::paiementBrouillonModifie->value)->sole()->details['montant'];
        $this->assertEquals([3000.0, 3500.0], [(float) $ecart['avant'], (float) $ecart['apres']]);   // l'avant et l'après au journal

        $this->post(route('comptabilite.reglement.soumettre', $brouillon))->assertSessionHas('success');
        $this->assertTrue($brouillon->refresh()->estSoumis());
        $this->assertSame(6500.0, $attribution->refresh()->resteDu());

        // Soumis, il est définitif.
        $this->delete(route('comptabilite.reglement.supprimer', $brouillon))->assertSessionHas('error');
        $this->assertDatabaseHas('paiements', ['id_paiement' => $brouillon->id_paiement]);

        // Un autre brouillon se supprime sans laisser de règlement.
        $this->post(route('comptabilite.reglement.creer', $attribution), $this->paiement(['montant' => 100]));
        $autre = $attribution->paiements()->brouillons()->sole();
        $this->delete(route('comptabilite.reglement.supprimer', $autre))->assertSessionHas('success');
        $this->assertDatabaseMissing('paiements', ['id_paiement' => $autre->id_paiement]);
    }

    public function test_la_saisie_est_validee_et_un_depassement_est_explique(): void
    {
        $attribution = $this->attributionPubliee();
        $url = route('comptabilite.reglement.creer', $attribution->id_attribution);
        $this->actingAs(User::role('comptable')->first());

        $this->post($url, $this->paiement(['montant' => 0]))->assertSessionHasErrors('montant');
        $this->post($url, $this->paiement(['mode' => 'troc']))->assertSessionHasErrors('mode');
        $this->post($url, $this->paiement(['date_paiement' => today()->addDay()->toDateString()]))->assertSessionHasErrors('date_paiement');

        $this->post($url, $this->paiement(['montant' => 99999]))->assertSessionHas('error');
        $this->assertSame(0, $attribution->paiements()->count());
    }

    public function test_le_comptable_retrouve_une_attribution_par_son_numero_ou_le_demandeur(): void
    {
        $attribution = $this->attributionPubliee();
        $autre = $this->attributionPubliee(Acteur::create(['nom' => 'Traoré', 'telephone' => '0102030405', 'date_naissance' => '1985-03-03']));
        $service = app(ReglementService::class);

        $this->assertSame([$attribution->id_attribution], $service->rechercherAttributions('ATT-'.$attribution->id_attribution)->pluck('id_attribution')->all());
        $this->assertSame([$attribution->id_attribution], $service->rechercherAttributions((string) $attribution->id_attribution)->pluck('id_attribution')->all());
        $this->assertSame([$autre->id_attribution], $service->rechercherAttributions('DEM-'.$autre->id_demande)->pluck('id_attribution')->all());
        $this->assertSame([$autre->id_attribution], $service->rechercherAttributions('01 02 03 04 05')->pluck('id_attribution')->all());
        $this->assertSame([$attribution->id_attribution], $service->rechercherAttributions('Kouas')->pluck('id_attribution')->all());

        $this->actingAs(User::role('comptable')->first())
            ->get(route('comptabilite.attributions', ['q' => 'Traoré']))
            ->assertOk()
            ->assertSee('ATT-'.str_pad($autre->id_attribution, 3, '0', STR_PAD_LEFT))
            ->assertSee('Enregistrer un règlement')
            ->assertDontSee('ATT-'.str_pad($attribution->id_attribution, 3, '0', STR_PAD_LEFT));

        $this->get(route('comptabilite.reglement', $autre))->assertOk()->assertSee('Enregistrer en brouillon');
    }

    // ----------------------------------------------------------------- Blocage

    public function test_un_acteur_en_impaye_ne_peut_pas_deposer_une_nouvelle_demande(): void
    {
        $this->attributionPubliee();
        $avant = Demande::count();
        $this->actingAs(User::role('dp')->first());

        try {
            app(CreationDemandeService::class)->creer($this->donneesDemande());
            $this->fail('Un acteur en impayé ne redemande pas.');
        } catch (RegleMetierViolee $e) {
            $this->assertStringContainsString('Kouassi', $e->getMessage());
            $this->assertStringContainsString('10 000 F', str_replace("\u{202F}", ' ', str_replace("\u{00A0}", ' ', $e->getMessage())));
        }

        $this->assertSame($avant, Demande::count());
    }

    public function test_la_route_renvoie_le_motif_du_refus_et_garde_la_saisie(): void
    {
        $this->attributionPubliee();

        $this->actingAs(User::role('dp')->first())
            ->from(route('demandes.create'))
            ->post(route('demandes.store'), $this->donneesDemande())
            ->assertRedirect(route('demandes.create'))
            ->assertSessionHas('error')
            ->assertSessionHasInput('nom', 'Kouassi');
    }

    public function test_le_blocage_se_leve_au_dernier_reglement(): void
    {
        $attribution = $this->attributionPubliee();
        $this->actingAs(User::role('admin')->first());
        $this->regler($attribution, $this->paiement(['montant' => 10000]));

        $this->actingAs(User::role('dp')->first());
        $demande = app(CreationDemandeService::class)->creer($this->donneesDemande());

        $this->assertSame($this->acteur->id_acteur, $demande->id_acteur);
    }

    public function test_un_autre_acteur_nest_pas_bloque(): void
    {
        $this->attributionPubliee();
        $this->actingAs(User::role('dp')->first());

        $demande = app(CreationDemandeService::class)->creer($this->donneesDemande(['nom' => 'Autre', 'date_naissance' => '1990-05-05', 'telephone' => '0799999999']));

        $this->assertNotSame($this->acteur->id_acteur, $demande->id_acteur);
    }

    public function test_une_serie_est_annulee_en_entier_si_un_acteur_est_bloque(): void
    {
        $this->attributionPubliee();
        $this->actingAs(User::role('dp')->first());
        $avant = Demande::count();

        $this->expectException(RegleMetierViolee::class);

        try {
            app(CreationDemandeSerieService::class)->creer([
                'id_type_acteur' => 1,
                'id_campagne' => Campagne::enCours()->id_campagne,
                'localites' => [1],
                'periode_debut_mise_en_place' => '2026-11-01',
                'periode_fin_mise_en_place' => '2027-02-01',
                'appuis' => [['id_type_appui' => 1, 'id_appui' => 1]],
            ], [
                ['nom' => 'Libre', 'date_naissance' => '1991-01-01', 'telephone' => '0701'],
                ['nom' => 'Kouassi', 'date_naissance' => '1980-01-01', 'telephone' => '0700000000'],
            ]);
        } finally {
            $this->assertSame($avant, Demande::count());
            $this->assertSame(0, Acteur::where('nom', 'Libre')->count());
        }
    }

    public function test_la_recherche_dacteur_signale_le_reste_du(): void
    {
        $this->attributionPubliee();

        $this->actingAs(User::role('dp')->first())
            ->getJson(route('acteurs.rechercher', ['nom' => 'Kouassi', 'date_naissance' => '1980-01-01']))
            ->assertOk()
            ->assertJsonPath('0.reste_du', 10000);
    }

    // --------------------------------------------------------------- Tableau de bord

    public function test_laccueil_de_ladmin_liste_les_acteurs_et_attributions_non_soldes(): void
    {
        $attribution = $this->attributionPubliee();
        $attribution->paiements()->create($this->paiementSoumis(['montant' => 4000]));
        $soldee = $this->attributionPubliee(Acteur::create(['nom' => 'Soldé', 'telephone' => '0702', 'date_naissance' => '1975-02-02']));
        $soldee->paiements()->create($this->paiementSoumis(['montant' => 10000]));

        $this->actingAs(User::role('admin')->first())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Acteurs en impayé')
            ->assertSee('Kouassi')
            ->assertSee('Brouillons à soumettre');

        // L'acteur soldé figure parmi les dernières attributions, mais pas parmi les impayés.
        $this->assertSame(['Kouassi'], app(ReglementService::class)->acteursEnImpaye()->pluck('acteur.nom')->all());

        $synthese = app(ReglementService::class)->synthese();
        $this->assertSame(20000.0, $synthese['du']);
        $this->assertSame(14000.0, $synthese['paye']);
        $this->assertSame(6000.0, $synthese['reste']);
        $this->assertSame(1, $synthese['nb_acteurs']);
        $this->assertSame(70.0, $synthese['taux']);
    }

    public function test_les_autres_roles_ne_voient_pas_la_caisse(): void
    {
        $this->attributionPubliee();

        foreach (['dp', 'agent-commission', 'gestionnaire-stock'] as $role) {
            $this->actingAs(User::role($role)->first())
                ->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('Acteurs en impayé');
        }
    }

    public function test_la_fiche_de_lattribution_montre_le_reglement(): void
    {
        $attribution = $this->attributionPubliee();
        $attribution->paiements()->create($this->paiementSoumis(['montant' => 4000]));

        $this->actingAs(User::role('comptable')->first())
            ->get(route('attributions.show', $attribution->id_attribution))
            ->assertOk()
            ->assertSee('Reste à payer')
            ->assertSee(route('comptabilite.reglement', $attribution->id_attribution), false);

        // Le DP voit où il en est, mais ne tient pas la caisse.
        $attribution->demande->update(['id_user' => User::role('dp')->value('id')]);
        $this->actingAs(User::role('dp')->first())
            ->get(route('attributions.show', $attribution->id_attribution))
            ->assertOk()
            ->assertSee('Reste à payer')
            ->assertDontSee('Enregistrer un règlement');
    }
}
