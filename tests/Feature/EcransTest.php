<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\Demande;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Chaque écran principal s'affiche sans erreur sur le jeu de démonstration.
 * Un modèle ou une relation renommés qui casse un écran se voit ici.
 */
class EcransTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemonstrationSeeder::class);
        $this->actingAs(User::role('admin')->first());
    }

    public function test_les_ecrans_de_demandes(): void
    {
        $demande = Demande::whereNotNull('date_soummission')->first();

        $this->get(route('demandes.index'))->assertOk();
        $this->get(route('demandes.create'))->assertOk();
        $this->get(route('demandes.show', $demande->id_demande))->assertOk();
    }

    public function test_la_liste_des_demandes_se_recherche_et_se_filtre_par_statut(): void
    {
        $demande = Demande::with('acteur')->whereNotNull('date_soummission')->doesntHave('attribution')->whereNull('motifs_rejet')->firstOrFail();
        $nom = $demande->nom_structure ?? $demande->acteur->nom;

        $this->get(route('demandes.index', ['q' => $nom]))->assertOk()->assertSee($nom);
        $this->get(route('demandes.index', ['q' => '#'.$demande->id_demande]))->assertOk()->assertSee($nom);
        $this->get(route('demandes.index', ['q' => 'introuvable-xyz']))->assertOk()->assertSee('Aucune demande à afficher');

        // Une demande soumise sans suite n'apparaît ni parmi les brouillons, ni parmi les attributions.
        $this->get(route('demandes.index', ['q' => '#'.$demande->id_demande, 'statut' => StatutDemandeE::soumise->value]))->assertSee($nom);
        $this->get(route('demandes.index', ['q' => '#'.$demande->id_demande, 'statut' => StatutDemandeE::brouillon->value]))->assertDontSee($nom);
    }

    public function test_les_ecrans_dattributions(): void
    {
        $enSeance = Attribution::whereHas('session', fn ($q) => $q->whereNull('date_fin_session'))->first();
        $publiee = Attribution::whereNotNull('date_publication')->first();

        $this->get(route('attributions.index'))->assertOk();
        $this->get(route('attributions.show', $enSeance->id_attribution))->assertOk();
        $this->get(route('attributions.edit', $enSeance->id_attribution))->assertOk();     // séance ouverte : modifiable
        $this->get(route('attributions.show', $publiee->id_attribution))->assertOk();
        $this->get(route('attributions.bon', $publiee->id_attribution))->assertOk();       // publiée : bon imprimable
        $this->get(route('attributions.courrier', $publiee->id_attribution))->assertOk()->assertSee('Objet');  // courrier de notification
    }

    public function test_les_ecrans_de_commission(): void
    {
        $session = SessionCommission::first();

        $this->get(route('commissions.index'))->assertOk();
        $this->get(route('commissions.synthese'))->assertOk();          // la synthèse imprimable
        $this->get(route('commissions.show', $session->id_session))->assertOk();
        $this->get(route('attributions.simulation'))->assertOk();
    }

    public function test_les_ecrans_du_magasin(): void
    {
        $aRetirer = Attribution::aDestocker()->first();

        $this->get(route('enlevements.index'))->assertOk();
        $this->get(route('enlevements.create', $aRetirer->id_attribution))->assertOk();
        $this->get(route('approvisionnements.index'))->assertOk();
        $this->get(route('approvisionnements.create'))->assertOk();
        $this->get(route('mouvements-stocks.index'))->assertOk();
        $this->get(route('mouvements-stocks.index', ['id_type_mouvement' => TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE), 'search' => 'a']))->assertOk()->assertSee('Sortie');
        $this->get(route('mouvements-stocks.show', MouvementStock::whereNotNull('id_attribution')->first()->id_mouvement_stock))
            ->assertOk()->assertSee('Attribution ATT-');
        $this->get(route('equipements.index'))->assertOk();
        $this->get(route('appuis.index'))->assertOk();
    }

    public function test_le_profil_utilise_la_coquille_de_lapplication(): void
    {
        $this->get(route('profile.edit'))->assertOk()->assertSee('Mon profil')->assertSee('Mot de passe actuel');
    }

    public function test_les_editions(): void
    {
        $this->get(route('editions.attributions.index'))->assertOk();
        $this->get(route('editions.arbitrages.index'))->assertOk();
    }

    public function test_le_magasin_filtre_le_parc_dequipements(): void
    {
        $equipement = Equipement::with('appui')->firstOrFail();

        $this->get(route('equipements.index', ['q' => $equipement->matricule_equipement]))
            ->assertOk()->assertSee($equipement->matricule_equipement);
        $this->get(route('equipements.index', ['q' => 'matricule-introuvable']))
            ->assertOk()->assertSee('Aucun équipement ne correspond');
        $this->get(route('equipements.index', ['situation' => 'remis', 'etat' => $equipement->etat]))->assertOk();
    }

    public function test_les_ecrans_du_magasin_sont_reserves_au_magasin(): void
    {
        $this->actingAs(User::role('dp')->first());

        $this->get(route('appuis.index'))->assertForbidden();
        $this->get(route('equipements.index'))->assertForbidden();
        $this->get(route('mouvements-stocks.index'))->assertForbidden();
    }
}
