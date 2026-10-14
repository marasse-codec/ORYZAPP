<?php

namespace Tests\Feature;

use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Services\SoumissionDemandeService;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** On ne soumet pas une demande sans visite de terrain enregistrée. */
class VisiteSoumissionTest extends TestCase
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

    private function brouillon(): Demande
    {
        $demande = $this->demande();
        $this->ligne($demande, 1, 1, 5);

        return $demande;
    }

    public function test_la_route_refuse_de_soumettre_sans_visite(): void
    {
        $demande = $this->brouillon();

        $this->post(route('demandes.soumettre', $demande->id_demande))
            ->assertRedirect(route('demandes.index'))
            ->assertSessionHas('error');

        $this->assertTrue($demande->fresh()->estBrouillon());
        $this->assertSame(0, JournalAction::where('action', ActionJournalEnum::demandeSoumise->value)->count());
    }

    public function test_la_visite_ouvre_la_soumission(): void
    {
        $demande = $this->brouillon();

        $this->post(route('demandes.visite.valider'), ['id_demande' => $demande->id_demande, 'observations' => 'Parcelle conforme'])
            ->assertRedirect(route('demandes.index'));

        $demande->refresh();
        $this->assertTrue($demande->terrainAEteVisite());
        $this->assertSame('Parcelle conforme', $demande->observations);
        $this->assertSame(1, JournalAction::where('action', ActionJournalEnum::demandeVisitee->value)->count());

        $this->post(route('demandes.soumettre', $demande->id_demande))->assertSessionHas('success');
        $this->assertTrue($demande->fresh()->estSoumise());
    }

    public function test_le_service_tient_la_regle_meme_hors_ecran(): void
    {
        $demande = $this->brouillon();

        try {
            app(SoumissionDemandeService::class)->soumettre($demande);
            $this->fail('La soumission sans visite aurait dû être refusée.');
        } catch (RegleMetierViolee $e) {
            $this->assertStringContainsString('visite de terrain', $e->getMessage());
        }

        $this->assertTrue($demande->fresh()->estBrouillon());
        $this->assertSame(0, JournalAction::count());
    }

    public function test_la_liste_naffiche_soumettre_quapres_la_visite(): void
    {
        $demande = $this->brouillon();
        $actionSoumettre = route('demandes.soumettre', $demande->id_demande);

        // D'autres brouillons (jeu de base) peuvent porter « Valider la visite » : on regarde l'action de CETTE demande.
        $this->get(route('demandes.index'))->assertDontSee($actionSoumettre, false);

        $demande->marquerVisite('Parcelle conforme');

        $this->get(route('demandes.index'))->assertSee($actionSoumettre, false);
    }
}
