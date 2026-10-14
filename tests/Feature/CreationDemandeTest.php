<?php

namespace Tests\Feature;

use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\PieceDemande;
use App\Domain\Demande\Services\CreationDemandeService;
use App\Domain\Referentiel\Models\Campagne;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** La création d'une demande : le service, son atomicité, puis la route qui l'appelle. */
class CreationDemandeTest extends TestCase
{
    use RefreshDatabase;

    private User $dp;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('public');
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);

        $this->dp = User::role('dp')->first();
        $this->actingAs($this->dp);
    }

    /** Données validées d'un formulaire : appui 1 (service 1) et type 3 sans appui précis (service 2). */
    private function donnees(array $surcharge = []): array
    {
        return $surcharge + [
            'id_type_acteur' => 1,
            'nom' => 'Kouassi',
            'telephone' => '0700000000',
            'email' => 'kouassi@example.com',
            'date_naissance' => '1980-01-01',
            'localites' => [1, 2],
            'id_campagne' => Campagne::enCours()->id_campagne,
            'periode_debut_mise_en_place' => '2026-11-01',
            'periode_fin_mise_en_place' => '2027-01-31',
            'superficies' => [1 => 2.5, 2 => 0],
            'lignes' => [
                ['id_type_appui' => 1, 'id_appui' => 1, 'quantite_demandee' => 5],
                ['id_type_appui' => 3, 'id_appui' => null, 'quantite_demandee' => ''],
            ],
        ];
    }

    public function test_le_service_cree_la_demande_avec_tout_ce_qui_la_constitue(): void
    {
        $pieces = [1 => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf')];

        $demande = app(CreationDemandeService::class)->creer($this->donnees(), $pieces);

        $this->assertTrue($demande->estBrouillon());
        $this->assertSame($this->dp->id, $demande->id_user);
        $this->assertDatabaseHas('acteurs', ['nom' => 'Kouassi', 'telephone' => '0700000000']);
        $this->assertCount(2, $demande->localites);

        // Seules les superficies > 0 sont gardées.
        $this->assertSame([1], $demande->systemesProductions->pluck('id_type_systeme_production')->all());

        // Une quantité vide devient null, un appui absent aussi.
        $lignes = $demande->lignes->keyBy('id_type_appui');
        $this->assertSame(5.0, (float) $lignes[1]->quantite_demandee);
        $this->assertNull($lignes[3]->quantite_demandee);
        $this->assertNull($lignes[3]->id_appui);

        // Une correspondance demande-service par service concerné : 1 et 2.
        $this->assertEqualsCanonicalizing([1, 2], $demande->traitements->pluck('id_service')->all());

        $this->assertSame(1, PieceDemande::where('id_demande', $demande->id_demande)->count());
    }

    public function test_un_demandeur_deja_connu_est_reutilise_et_ses_coordonnees_rafraichies(): void
    {
        $service = app(CreationDemandeService::class);
        $service->creer($this->donnees());
        $service->creer($this->donnees(['telephone' => '0711111111']));

        $this->assertSame(1, Acteur::where('nom', 'Kouassi')->count());
        $this->assertSame('0711111111', Acteur::where('nom', 'Kouassi')->value('telephone'));
        $this->assertSame(2, Demande::where('id_acteur', Acteur::where('nom', 'Kouassi')->value('id_acteur'))->count());
    }

    public function test_une_erreur_annule_tout_y_compris_le_demandeur(): void
    {
        $avantActeurs = Acteur::count();
        $avantDemandes = Demande::count();

        // Le type d'appui 999 n'existe pas : la clé étrangère refuse la ligne, après la création de la demande.
        $donnees = $this->donnees([
            'nom' => 'Nouveau demandeur',
            'lignes' => [['id_type_appui' => 999, 'id_appui' => null, 'quantite_demandee' => 1]],
        ]);

        try {
            app(CreationDemandeService::class)->creer($donnees);
            $this->fail('La ligne invalide aurait dû faire échouer la création.');
        } catch (QueryException) {
            // attendu
        }

        $this->assertSame($avantActeurs, Acteur::count());
        $this->assertSame($avantDemandes, Demande::count());
    }

    public function test_la_route_enregistre_la_demande_puis_redirige_vers_sa_fiche(): void
    {
        $reponse = $this->post(route('demandes.store'), $this->donnees());

        $demande = Demande::where('id_acteur', Acteur::where('nom', 'Kouassi')->value('id_acteur'))->firstOrFail();
        $reponse->assertRedirect(route('demandes.show', $demande->id_demande));
    }

    public function test_les_erreurs_de_saisie_designent_le_champ_en_cause(): void
    {
        // SNF03 / SNF04 : refus de saisie, message ciblant le champ.
        $donnees = $this->donnees();
        unset($donnees['localites']);
        $donnees['periode_fin_mise_en_place'] = '2026-10-01';       // avant le début

        // L'application ne rend du JSON que pour api/* : une saisie refusée revient au formulaire avec ses erreurs.
        $this->post(route('demandes.store'), $donnees)
            ->assertSessionHasErrors(['localites', 'periode_fin_mise_en_place']);

        $this->assertDatabaseMissing('acteurs', ['nom' => 'Kouassi']);
    }
}
