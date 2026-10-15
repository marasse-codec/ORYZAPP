<?php

namespace Tests\Feature;

use App\Domain\Commission\Enums\ModePaiementEnum;
use App\Domain\Commission\Models\LigneAttribution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Les deux nouveaux rôles : le directeur pilote sans rien modifier, le comptable tient la caisse. */
class DirectionComptabiliteTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function directeur(): User
    {
        return User::role('directeur')->firstOrFail();
    }

    private function comptable(): User
    {
        return User::role('comptable')->firstOrFail();
    }

    public function test_le_directeur_consulte_pilotage_statistiques_et_rapport(): void
    {
        $this->actingAs($this->directeur());

        $this->get(route('pilotage.index'))->assertOk()->assertSee('Pilotage')->assertSee('À arbitrer');
        $this->get(route('statistiques.index'))->assertOk();
        $this->get(route('statistiques.comparaison'))->assertOk();
        $this->get(route('statistiques.rapport'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_le_directeur_ne_saisit_ni_demande_ni_reglement(): void
    {
        $attribution = $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 2]));
        $this->actingAs($this->directeur());

        $this->post(route('demandes.store'), [])->assertForbidden();
        $this->post(route('comptabilite.reglement.creer', $attribution->id_attribution), [
            'montant' => 100, 'date_paiement' => today()->toDateString(), 'mode' => ModePaiementEnum::especes->value,
        ])->assertForbidden();
        $this->get(route('reglements.index'))->assertForbidden();
    }

    public function test_les_autres_roles_nont_pas_le_pilotage(): void
    {
        foreach (['dp', 'agent-commission', 'gestionnaire-stock', 'comptable'] as $role) {
            $this->actingAs(User::role($role)->first())->get(route('pilotage.index'))->assertForbidden();
        }
    }

    public function test_le_comptable_encaisse_et_suit_les_impayes(): void
    {
        $attribution = $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 5]));
        LigneAttribution::where('id_attribution', $attribution->id_attribution)->update(['cout_attribution' => 1000]);

        $this->actingAs($this->comptable());

        $this->get(route('reglements.index'))->assertOk()->assertSee('Reste à encaisser')->assertSee('Aucun règlement enregistré');

        $this->post(route('comptabilite.reglement.creer', $attribution->id_attribution), [
            'montant' => 2000, 'date_paiement' => today()->toDateString(), 'mode' => ModePaiementEnum::especes->value,
        ])->assertSessionHas('success');
        $this->post(route('comptabilite.reglement.soumettre', $attribution->paiements()->sole()))->assertSessionHas('success');

        $this->get(route('reglements.index'))->assertOk()->assertSee('2 000 F');
        $this->get(route('statistiques.rapport'))->assertOk();     // il télécharge le rapport de campagne
        $this->get(route('statistiques.index'))->assertForbidden(); // mais pas le détail des statistiques
        $this->post(route('demandes.store'), [])->assertForbidden(); // ni de saisie de demande
        $this->get(route('demandes.index'))->assertForbidden();     // ni la consultation des demandes
        $this->get(route('attributions.show', $attribution))->assertOk(); // il consulte les attributions

        // Son tableau de bord ne concerne que les règlements.
        $this->get(route('dashboard'))->assertOk()->assertSee('Règlements')->assertDontSee('Répartition des demandes');
    }
}
