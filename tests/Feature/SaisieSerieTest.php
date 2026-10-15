<?php

namespace Tests\Feature;

use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Services\CreationDemandeSerieService;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Models\User;
use Illuminate\Database\QueryException;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Saisie en série : un lot de demandes qui partagent campagne, localités et appuis. */
class SaisieSerieTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $dp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        $this->dp = User::role('dp')->first();
        $this->actingAs($this->dp);
    }

    /** Un lot valide : 2 appuis communs (type 1 / appui 1, type 3 sans appui précis) et trois acteurs. */
    private function lot(array $surcharge = []): array
    {
        return $surcharge + [
            'id_campagne' => $this->campagne->id_campagne,
            'id_type_acteur' => 1,
            'localites' => [1, 2],
            'periode_debut_mise_en_place' => '2026-11-01',
            'periode_fin_mise_en_place' => '2027-02-01',
            'appuis' => [
                ['id_type_appui' => 1, 'id_appui' => 1],
                ['id_type_appui' => 3, 'id_appui' => ''],
            ],
            'acteurs' => [
                ['nom' => 'Kouassi Yao', 'date_naissance' => '1980-03-12', 'telephone' => '07 01 02 03 04', 'nom_structure' => '', 'visite' => 'Parcelle conforme', 'quantites' => [50, 2]],
                ['nom' => 'Traoré Awa', 'date_naissance' => '1975-07-01', 'telephone' => '0705060708', 'nom_structure' => 'GVC Soleil', 'visite' => '', 'quantites' => [30, '']],
                ['nom' => 'Konan Brou', 'date_naissance' => '1990-11-25', 'telephone' => '0102030405', 'nom_structure' => '', 'visite' => '', 'quantites' => ['', '']],
                ['nom' => '', 'date_naissance' => '', 'telephone' => '', 'nom_structure' => '', 'quantites' => ['', '']],   // ligne vide : écartée
            ],
        ];
    }

    // ------------------------------------------------------------------ Accès

    public function test_lecran_et_le_menu_pour_le_dp(): void
    {
        $this->get(route('demandes.serie.create'))
            ->assertOk()
            ->assertSee('Saisie en série')
            ->assertSee('Coller depuis un tableur');

        // Le menu propose l'écran : l'URL ne doit pas être prise pour la fiche d'une demande « serie ».
        $this->get(route('demandes.index'))->assertSee(route('demandes.serie.create'));
    }

    public function test_un_agent_peut_aussi_creer_des_demandes_en_serie(): void
    {
        foreach (['agent-commission', 'agent-service'] as $role) {
            $this->actingAs(User::role($role)->first())->get(route('demandes.serie.create'))->assertOk();
        }

        // Le magasin ne saisit pas de demandes : il ne s'occupe que des stocks.
        $this->actingAs(User::role('gestionnaire-stock')->first())->get(route('demandes.serie.create'))->assertForbidden();

        $agent = User::role('agent-service')->first();
        $this->actingAs($agent)->post(route('demandes.serie.store'), $this->lot())->assertSessionHas('success');
        $this->assertSame($agent->id, Demande::whereHas('acteur', fn ($q) => $q->where('nom', 'Kouassi Yao'))->sole()->id_user);
    }

    // ---------------------------------------------------------------- Enregistrement

    public function test_le_lot_cree_une_demande_en_brouillon_par_acteur(): void
    {
        $avant = Demande::count();

        $this->post(route('demandes.serie.store'), $this->lot())
            ->assertRedirect(route('demandes.index'))
            ->assertSessionHas('success');

        $this->assertSame($avant + 3, Demande::count());                 // la ligne vide n'a pas créé de demande
        $this->assertSame(3, JournalAction::where('action', ActionJournalEnum::demandeCreee->value)->count());

        $kouassi = Demande::whereHas('acteur', fn ($q) => $q->where('nom', 'Kouassi Yao'))->sole();
        $this->assertTrue($kouassi->estBrouillon());
        $this->assertSame($this->dp->id, $kouassi->id_user);
        $this->assertSame('0701020304', $kouassi->acteur->telephone);    // espaces retirés
        $this->assertEqualsCanonicalizing([1, 2], $kouassi->localites->pluck('id_localite')->all());

        // Une ligne par appui commun, avec la quantité propre à l'acteur.
        $lignes = $kouassi->lignes->keyBy('id_type_appui');
        $this->assertSame(50.0, (float) $lignes[1]->quantite_demandee);
        $this->assertSame(1, $lignes[1]->id_appui);
        $this->assertSame(2.0, (float) $lignes[3]->quantite_demandee);
        $this->assertNull($lignes[3]->id_appui);

        // Quantité laissée vide : null, comme dans la saisie unitaire.
        $traore = Demande::whereHas('acteur', fn ($q) => $q->where('nom', 'Traoré Awa'))->sole();
        $this->assertNull($traore->lignes->firstWhere('id_type_appui', 3)->quantite_demandee);
        $this->assertSame('GVC Soleil', $traore->nom_structure);

        // Comme à la création unitaire, la demande est rattachée aux services dont elle sollicite les appuis (1 et 2).
        $this->assertEqualsCanonicalizing([1, 2], $kouassi->traitements->pluck('id_service')->all());

        Notification::assertNothingSent();                               // pas de notification de création
    }

    public function test_les_observations_de_visite_sont_enregistrees_avec_la_demande(): void
    {
        $this->post(route('demandes.serie.store'), $this->lot())->assertSessionHas('success');

        $kouassi = Demande::whereHas('acteur', fn ($q) => $q->where('nom', 'Kouassi Yao'))->sole();
        $this->assertTrue($kouassi->terrainAEteVisite());
        $this->assertSame('Parcelle conforme', $kouassi->observations);
        $this->assertTrue($kouassi->estBrouillon());                       // visitée, pas soumise

        $traore = Demande::whereHas('acteur', fn ($q) => $q->where('nom', 'Traoré Awa'))->sole();
        $this->assertFalse($traore->terrainAEteVisite());                  // sans observations : pas de visite

        $this->assertSame(1, JournalAction::where('action', ActionJournalEnum::demandeVisitee->value)->count());
    }

    /** Un lot dont chaque ligne porte sa visite de terrain, prêt à être soumis. */
    private function lotVisite(array $surcharge = []): array
    {
        $lot = $this->lot($surcharge);
        foreach ($lot['acteurs'] as $i => $acteur) {
            if ($acteur['nom'] !== '') {
                $lot['acteurs'][$i]['visite'] = 'Visite du '.($i + 1).' octobre : conforme';
            }
        }

        return $lot;
    }

    public function test_soumettre_aussitot_exige_la_visite_de_chaque_ligne(): void
    {
        $avant = [Demande::count(), Acteur::count()];

        // Les lignes 2 et 3 n'ont pas de visite : on ne soumet pas sans visite.
        $this->post(route('demandes.serie.store'), $this->lot(['soumettre' => '1']))
            ->assertSessionHasErrors(['acteurs.1.visite', 'acteurs.2.visite']);

        $messages = collect(session('errors')->all());
        $this->assertTrue($messages->contains('Ligne 2 : une visite de terrain doit être renseignée pour soumettre la demande.'));
        $this->assertFalse($messages->contains(fn ($m) => str_starts_with($m, 'Ligne 1 :')));
        $this->assertSame($avant, [Demande::count(), Acteur::count()]);     // rien n'est créé
    }

    public function test_le_service_refuse_de_soumettre_sans_visite_et_annule_tout_le_lot(): void
    {
        $avant = [Demande::count(), Acteur::count(), JournalAction::count()];
        $lot = $this->lot();                                                // la ligne 2 n'a pas de visite

        try {
            app(CreationDemandeSerieService::class)->creer($lot, array_slice($lot['acteurs'], 0, 2), soumettre: true);
            $this->fail('La ligne sans visite aurait dû empêcher la soumission.');
        } catch (RegleMetierViolee $e) {
            $this->assertStringContainsString('visite de terrain', $e->getMessage());
        }

        // La ligne 1 (visitée, soumise) a été défaite avec le reste.
        $this->assertSame($avant, [Demande::count(), Acteur::count(), JournalAction::count()]);
    }

    public function test_soumettre_aussitot_soumet_toutes_les_demandes_du_lot(): void
    {
        $this->post(route('demandes.serie.store'), $this->lotVisite(['soumettre' => '1']))->assertSessionHas('success');

        $lot = Demande::whereHas('acteur', fn ($q) => $q->whereIn('telephone', ['0701020304', '0705060708', '0102030405']))->get();
        $this->assertCount(3, $lot);
        $this->assertTrue($lot->every(fn (Demande $d) => $d->estSoumise()));
        $this->assertSame(3, JournalAction::where('action', ActionJournalEnum::demandeSoumise->value)->count());
        $this->assertSame(3, JournalAction::where('action', ActionJournalEnum::demandeVisitee->value)->count());
    }

    public function test_un_acteur_deja_connu_est_reutilise(): void
    {
        $this->post(route('demandes.serie.store'), $this->lot());
        $acteurs = Acteur::count();

        // Même nom, même date de naissance, même téléphone : le même acteur, une nouvelle demande.
        $this->post(route('demandes.serie.store'), $this->lot(['acteurs' => [
            ['nom' => 'Kouassi Yao', 'date_naissance' => '1980-03-12', 'telephone' => '0701020304', 'quantites' => [10, '']],
        ]]))->assertSessionHasNoErrors();

        $this->assertSame($acteurs, Acteur::count());
        $this->assertSame(2, Demande::whereHas('acteur', fn ($q) => $q->where('nom', 'Kouassi Yao'))->count());
    }

    // ------------------------------------------------------------------ Validation

    public function test_une_ligne_invalide_refuse_tout_le_lot_avec_son_numero(): void
    {
        $lot = $this->lot();
        $lot['acteurs'][1]['nom'] = '';                                  // ligne 2 : sans nom
        $lot['acteurs'][2]['telephone'] = '123';                         // ligne 3 : téléphone invalide
        $avant = [Demande::count(), Acteur::count()];

        $reponse = $this->post(route('demandes.serie.store'), $lot);

        $reponse->assertSessionHasErrors(['acteurs.1.nom', 'acteurs.2.telephone']);
        $messages = collect(session('errors')->all());
        $this->assertTrue($messages->contains('Ligne 2 : le nom est obligatoire.'));
        $this->assertTrue($messages->contains('Ligne 3 : le téléphone doit comporter 10 chiffres.'));
        $this->assertSame($avant, [Demande::count(), Acteur::count()]);   // rien n'est créé, pas même la ligne 1 valide
    }

    public function test_les_doublons_dans_la_serie_sont_signales(): void
    {
        $lot = $this->lot();
        $lot['acteurs'][1]['telephone'] = '0701020304';                  // même téléphone que la ligne 1

        $this->post(route('demandes.serie.store'), $lot)->assertSessionHasErrors('acteurs.1.telephone');
        $this->assertTrue(collect(session('errors')->all())->contains('Ligne 2 : ce téléphone figure déjà à la ligne 1.'));
    }

    public function test_un_telephone_deja_attribue_a_un_autre_acteur_est_refuse(): void
    {
        Acteur::create(['nom' => 'Autre Personne', 'date_naissance' => '1960-01-01', 'telephone' => '0705060708']);

        $this->post(route('demandes.serie.store'), $this->lot())->assertSessionHasErrors('acteurs.1.telephone');
        $this->assertTrue(collect(session('errors')->all())->contains('Ligne 2 : ce téléphone appartient déjà à un autre acteur (Autre Personne).'));
    }

    public function test_les_appuis_communs_sont_coherents(): void
    {
        $this->post(route('demandes.serie.store'), $this->lot(['appuis' => [
            ['id_type_appui' => 1, 'id_appui' => 1],
            ['id_type_appui' => 1, 'id_appui' => ''],                    // même type d'appui deux fois
        ]]))->assertSessionHasErrors('appuis.1.id_type_appui');

        $this->post(route('demandes.serie.store'), $this->lot(['appuis' => [
            ['id_type_appui' => 3, 'id_appui' => 1],                     // l'appui 1 n'est pas de type 3
        ]]))->assertSessionHasErrors('appuis.0.id_appui');

        $this->post(route('demandes.serie.store'), $this->lot(['appuis' => []]))->assertSessionHasErrors('appuis');
    }

    public function test_les_champs_communs_et_la_taille_du_lot_sont_controles(): void
    {
        $lot = $this->lot(['periode_fin_mise_en_place' => '2026-10-01', 'localites' => []]);
        $this->post(route('demandes.serie.store'), $lot)->assertSessionHasErrors(['periode_fin_mise_en_place', 'localites']);

        $this->post(route('demandes.serie.store'), $this->lot(['acteurs' => []]))->assertSessionHasErrors('acteurs');
    }

    // ------------------------------------------------------------------ Atomicité

    public function test_une_defaillance_en_cours_de_lot_annule_toutes_les_demandes(): void
    {
        $commun = $this->lot();
        $avant = [Demande::count(), Acteur::count(), JournalAction::count()];

        // La ligne 2 réutilise le téléphone de la ligne 1 pour un autre acteur : la base refuse (unicité), après la ligne 1.
        $acteurs = [
            ['nom' => 'Premier', 'date_naissance' => '1980-01-01', 'telephone' => '0701010101', 'quantites' => [1, 1]],
            ['nom' => 'Second', 'date_naissance' => '1981-02-02', 'telephone' => '0701010101', 'quantites' => [1, 1]],
        ];

        try {
            app(CreationDemandeSerieService::class)->creer($commun, $acteurs);
            $this->fail('Le doublon de téléphone aurait dû faire échouer le lot.');
        } catch (QueryException) {
            // attendu
        }

        $this->assertSame($avant, [Demande::count(), Acteur::count(), JournalAction::count()]);
    }
}
