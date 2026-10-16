<?php

namespace Tests\Feature;

use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Services\CreationDemandeService;
use App\Domain\Referentiel\Models\Campagne;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Choisir l'année puis le cycle ; le profil (type d'acteur, structure) propre à chaque demande. */
class AnneeCycleEtProfilTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    // ------------------------------------------------------------ Année puis cycle

    public function test_un_cycle_appartient_a_lannee_de_son_debut(): void
    {
        $chevauche = Campagne::create(['lib_campagne' => 'Cycle 4', 'mois_debut_campagne' => '2025-11-01', 'mois_fin_campagne' => '2026-02-28']);

        $this->assertSame(2025, $chevauche->annee());
        $this->assertSame('Cycle 4 (2025)', $chevauche->libelleComplet());
        $this->assertContains(2025, Campagne::annees()->all());
        $this->assertSame(['Cycle 4'], Campagne::deLAnnee(2025)->pluck('lib_campagne')->all());
    }

    public function test_on_choisit_lannee_puis_un_de_ses_cycles(): void
    {
        $c1 = Campagne::create(['lib_campagne' => 'Cycle 1', 'mois_debut_campagne' => '2025-01-01', 'mois_fin_campagne' => '2025-04-01']);
        $c2 = Campagne::create(['lib_campagne' => 'Cycle 2', 'mois_debut_campagne' => '2025-04-01', 'mois_fin_campagne' => '2025-08-01']);
        $this->actingAs(User::role('admin')->first());

        // Une année sans cycle précis : son dernier cycle ; la liste ne propose que les cycles de cette année.
        $this->get(route('statistiques.index', ['annee' => 2025]))
            ->assertOk()
            ->assertViewHas('campagne', fn ($c) => $c->is($c2))
            ->assertViewHas('cycles', fn ($cycles) => $cycles->pluck('id_campagne')->all() === [$c1->id_campagne, $c2->id_campagne]);

        // Un cycle choisi dans l'année.
        $this->get(route('statistiques.index', ['annee' => 2025, 'campagne' => $c1->id_campagne]))
            ->assertViewHas('campagne', fn ($c) => $c->is($c1));

        // Un cycle d'une autre année que celle choisie : on reste dans l'année choisie.
        $this->get(route('statistiques.index', ['annee' => 2025, 'campagne' => $this->campagne->id_campagne]))
            ->assertViewHas('campagne', fn ($c) => $c->is($c2));

        // Sans rien : le cycle en cours et son année.
        $this->get(route('statistiques.index'))
            ->assertViewHas('campagne', fn ($c) => $c->is($this->campagne))
            ->assertViewHas('annee', $this->campagne->annee());

        // Le pilotage suit la même règle.
        $this->get(route('pilotage.index', ['annee' => 2025]))->assertOk()->assertViewHas('campagne', fn ($c) => $c->is($c2))->assertSee('Cycle 2 (2025)');
    }

    public function test_la_comparaison_prend_tous_les_cycles_dune_annee(): void
    {
        Campagne::create(['lib_campagne' => 'Cycle 1', 'mois_debut_campagne' => '2025-01-01', 'mois_fin_campagne' => '2025-04-01']);
        Campagne::create(['lib_campagne' => 'Cycle 2', 'mois_debut_campagne' => '2025-04-01', 'mois_fin_campagne' => '2025-08-01']);

        $this->actingAs(User::role('admin')->first())
            ->get(route('statistiques.comparaison', ['annee' => 2025]))
            ->assertOk()
            ->assertViewHas('lignes', fn ($lignes) => $lignes->map(fn ($l) => $l['campagne']->libelleComplet())->all() === ['Cycle 1 (2025)', 'Cycle 2 (2025)']);
    }

    // ------------------------------------------------------- Profil propre à la demande

    private function donnees(array $surcharge): array
    {
        return $surcharge + [
            'nom' => 'Kouamé Yao', 'date_naissance' => '1980-01-01', 'telephone' => '0712121212',
            'localites' => [1], 'id_campagne' => $this->campagne->id_campagne,
            'periode_debut_mise_en_place' => today()->toDateString(),
            'periode_fin_mise_en_place' => today()->addMonths(4)->toDateString(),
            'lignes' => [['id_type_appui' => 1, 'id_appui' => 1, 'quantite_demandee' => 1]],
        ];
    }

    public function test_un_acteur_venu_en_cooperative_revient_en_individuel_sans_structure(): void
    {
        $this->actingAs(User::role('dp')->first());
        $service = app(CreationDemandeService::class);

        $avant = $service->creer($this->donnees(['id_type_acteur' => 3, 'nom_structure' => 'Coopérative du centre']));
        $apres = $service->creer($this->donnees(['id_type_acteur' => 1, 'nom_structure' => 'Coopérative du centre']));

        $this->assertSame($avant->id_acteur, $apres->id_acteur);                // même acteur
        $this->assertSame('Coopérative du centre', $avant->nom_structure);
        $this->assertSame(3, $avant->id_type_acteur);
        $this->assertNull($apres->nom_structure);                              // en individuel : aucune structure
        $this->assertSame(1, $apres->id_type_acteur);
    }

    public function test_la_recherche_dacteur_ne_propose_plus_le_profil_precedent(): void
    {
        $acteur = Acteur::create(['nom' => 'Kouamé Yao', 'date_naissance' => '1980-01-01', 'telephone' => '0712121212']);
        $this->demande(['id_acteur' => $acteur->id_acteur, 'id_type_acteur' => 3, 'nom_structure' => 'Coopérative du centre']);

        $this->actingAs(User::role('dp')->first())
            ->getJson(route('acteurs.rechercher', ['nom' => 'Kouamé', 'date_naissance' => '1980-01-01']))
            ->assertOk()
            ->assertJsonMissingPath('0.id_type_acteur')
            ->assertJsonMissingPath('0.nom_structure')
            ->assertJsonPath('0.precedente', 'Coopérative — Coopérative du centre');
    }
}
