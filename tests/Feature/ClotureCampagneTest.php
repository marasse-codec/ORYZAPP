<?php

namespace Tests\Feature;

use App\Domain\Commission\Enums\ModePaiementEnum;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Commission\Services\AttributionService;
use App\Domain\Commission\Services\ReglementService;
use App\Domain\Commission\Services\RendementService;
use App\Domain\Demande\Services\CreationDemandeService;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Services\ClotureCampagneService;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Filament\Admin\Resources\CampagneResource\Pages\ManageCampagnes;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Clôturer une campagne la fige : plus de demande, d'attribution ni de rendement ; les règlements restent possibles. */
class ClotureCampagneTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
        $this->actingAs(User::role('admin')->firstOrFail());
    }

    private function cloturer(): void
    {
        app(ClotureCampagneService::class)->cloturer($this->campagne);
        $this->campagne->refresh();
    }

    public function test_ladmin_cloture_et_rouvre_depuis_ladministration(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageCampagnes::class)
            ->callTableAction('cloturer', $this->campagne)
            ->assertNotified('Campagne clôturée');
        $this->assertTrue($this->campagne->refresh()->estCloturee());
        $this->assertSame(1, JournalAction::where('action', ActionJournalEnum::campagneCloturee->value)->count());

        Livewire::test(ManageCampagnes::class)
            ->callTableAction('rouvrir', $this->campagne, ['motif' => 'Clôture prématurée'])
            ->assertNotified('Campagne rouverte');
        $this->assertFalse($this->campagne->refresh()->estCloturee());
        $this->assertSame('Clôture prématurée', JournalAction::where('action', ActionJournalEnum::campagneRouverte->value)->sole()->details['motif']);
    }

    public function test_plus_de_nouvelle_demande_sur_une_campagne_cloturee(): void
    {
        $this->cloturer();

        $this->expectException(RegleMetierViolee::class);
        $this->expectExceptionMessage('clôturée');

        app(CreationDemandeService::class)->creer([
            'id_type_acteur' => 1, 'nom' => 'Koffi', 'telephone' => '0701010101', 'date_naissance' => '1990-01-01',
            'id_campagne' => $this->campagne->id_campagne,
            'periode_debut_mise_en_place' => today()->toDateString(), 'periode_fin_mise_en_place' => today()->addMonth()->toDateString(),
        ]);
    }

    public function test_le_formulaire_ne_propose_que_les_campagnes_ouvertes(): void
    {
        $this->cloturer();

        $this->get(route('demandes.create'))
            ->assertOk()
            ->assertViewHas('campagnes', fn ($campagnes) => ! $campagnes->contains('id_campagne', $this->campagne->id_campagne));
    }

    public function test_plus_dattribution_ni_de_rendement_mais_les_reglements_continuent(): void
    {
        $demande = $this->demandeSoumise();
        $this->systeme($demande, 1, 2);
        $attribution = $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 2]));
        LigneAttribution::where('id_attribution', $attribution->id_attribution)->update(['cout_attribution' => 500]);
        $this->cloturer();

        try {
            app(AttributionService::class)->enregistrer($demande, $this->seance()->id_session, [['id_appui' => 1, 'quantite_attribuee' => 1]]);
            $this->fail('Une campagne clôturée ne reçoit plus d\'attribution.');
        } catch (RegleMetierViolee $e) {
            $this->assertStringContainsString('clôturée', $e->getMessage());
        }

        try {
            app(RendementService::class)->enregistrer($attribution, []);
            $this->fail('Une campagne clôturée ne reçoit plus de rendement.');
        } catch (RegleMetierViolee $e) {
            $this->assertStringContainsString('clôturée', $e->getMessage());
        }

        // Une dette ne disparaît pas avec la clôture.
        $reglements = app(ReglementService::class);
        $reglements->soumettre($reglements->creerBrouillon($attribution, ['montant' => 500, 'date_paiement' => today()->toDateString(), 'mode' => ModePaiementEnum::especes->value]));
        $this->assertSame(1, $attribution->paiements()->soumis()->count());
    }

    public function test_on_ne_cloture_pas_deux_fois(): void
    {
        $this->cloturer();

        $this->expectException(RegleMetierViolee::class);
        app(ClotureCampagneService::class)->cloturer(Campagne::find($this->campagne->id_campagne));
    }
}
