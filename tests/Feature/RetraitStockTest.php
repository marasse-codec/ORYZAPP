<?php

namespace Tests\Feature;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Le retrait au magasin : sorties de stock exactes, refus propres, tout ou rien. */
class RetraitStockTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $magasinier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
        Storage::fake('public');

        $this->magasinier = User::role('gestionnaire-stock')->first();
        $this->actingAs($this->magasinier);
    }

    /** Une attribution servable : séance close, résultats publiés, bon pas encore retiré. */
    private function aRetirer(array $lignes): Attribution
    {
        return $this->publier($this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), $lignes));
    }

    private function retirer(Attribution $attribution, array $surcharge = [])
    {
        return $this->patch(route('attributions.destocker', $attribution), $surcharge + [
            'date_destockage' => today()->toDateString(),
            'recipiendaire' => 'Kouassi Yao',
            'numero_piece_identite' => 'CI0001',
            'piece_identite' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
        ]);
    }

    public function test_le_retrait_dun_intrant_sort_le_stock_et_garde_la_trace(): void
    {
        $attribution = $this->aRetirer([1 => 20]);
        $stockAvant = Appui::find(1)->quantite_en_stock;

        $this->retirer($attribution)->assertRedirect(route('enlevements.index'));

        $this->assertSame($stockAvant - 20, Appui::find(1)->quantite_en_stock);

        // Un mouvement de sortie rattaché à l'attribution (la base l'exige : origine unique).
        $mouvement = MouvementStock::where('id_attribution', $attribution->id_attribution)->sole();
        $this->assertSame(1, $mouvement->id_appui);
        $this->assertSame(20, $mouvement->quantite);
        $this->assertSame($this->magasinier->agent->id_agent, $mouvement->id_agent);
        $this->assertNull($mouvement->id_approvisionnement);

        $attribution->refresh();
        $this->assertSame(today()->toDateString(), $attribution->date_destockage->toDateString());
        $this->assertSame('Kouassi Yao', $attribution->recipiendaire);     // le récipiendaire est bien conservé
        Storage::disk('public')->assertExists($attribution->chemin_piece_identite);

        $trace = JournalAction::where('action', ActionJournalEnum::attributionRetiree->value)->sole();
        $this->assertSame($this->magasinier->id, $trace->user_id);
        $this->assertTrue($trace->cible->is($attribution));
        $this->assertSame('Kouassi Yao', $trace->details['recipiendaire']);
    }

    public function test_le_retrait_declenche_les_observers_de_lappui(): void
    {
        // Les règles d'alerte de stock s'appuient sur l'événement « updated » de l'appui.
        $declenche = [];
        Appui::updated(function (Appui $appui) use (&$declenche) {
            $declenche[] = $appui->id_appui;
        });

        $this->retirer($this->aRetirer([1 => 20]))->assertRedirect(route('enlevements.index'));

        $this->assertContains(1, $declenche);
    }

    public function test_un_stock_insuffisant_refuse_tout_sans_rien_laisser(): void
    {
        $attribution = $this->aRetirer([1 => 20, 2 => 500]);   // appui 2 : 120 en stock seulement
        $stock1 = Appui::find(1)->quantite_en_stock;
        $stock2 = Appui::find(2)->quantite_en_stock;

        $this->retirer($attribution)->assertSessionHas('error', fn ($m) => str_contains($m, 'Stock insuffisant'));

        // La première ligne était valable, mais tout est annulé avec la seconde.
        $this->assertSame($stock1, Appui::find(1)->quantite_en_stock);
        $this->assertSame($stock2, Appui::find(2)->quantite_en_stock);
        $this->assertSame(0, MouvementStock::where('id_attribution', $attribution->id_attribution)->count());
        $this->assertNull($attribution->fresh()->date_destockage);
        $this->assertSame(0, JournalAction::count());
        $this->assertSame([], Storage::disk('public')->allFiles());   // la pièce d'identité téléversée est retirée
    }

    public function test_un_bon_ne_se_retire_quune_fois(): void
    {
        $attribution = $this->aRetirer([1 => 20]);
        $stockAvant = Appui::find(1)->quantite_en_stock;

        $this->retirer($attribution);
        $this->retirer($attribution)->assertSessionHas('error', fn ($m) => str_contains($m, 'déjà été retirée, ou ses résultats ne sont pas encore publiés'));

        $this->assertSame($stockAvant - 20, Appui::find(1)->quantite_en_stock);   // déstocké une seule fois
        $this->assertSame(1, MouvementStock::where('id_attribution', $attribution->id_attribution)->count());
    }

    public function test_un_bon_dont_les_resultats_ne_sont_pas_publies_ne_se_retire_pas(): void
    {
        $nonPublie = $this->attribuer($this->demandeSoumise(), $this->seance(cloturee: true), [1 => 20]);
        $stockAvant = Appui::find(1)->quantite_en_stock;

        $this->retirer($nonPublie)->assertSessionHas('error', fn ($m) => str_contains($m, 'pas encore publiés'));
        $this->get(route('enlevements.create', $nonPublie->id_attribution))->assertRedirect(route('enlevements.index'));

        $this->assertSame($stockAvant, Appui::find(1)->quantite_en_stock);
        $this->assertNull($nonPublie->fresh()->date_destockage);

        // Une fois publié, le même bon se sert.
        $this->publier($nonPublie);
        $this->retirer($nonPublie)->assertRedirect(route('enlevements.index'));
    }

    public function test_un_bon_dune_seance_ouverte_ne_se_retire_pas(): void
    {
        $attribution = $this->attribuer($this->demandeSoumise(), $this->seance(), [1 => 20]);

        $this->retirer($attribution)->assertSessionHas('error', fn ($m) => str_contains($m, 'déjà été retirée, ou ses résultats ne sont pas encore publiés'));

        $this->assertNull($attribution->fresh()->date_destockage);
    }

    public function test_le_retrait_de_materiel_met_les_equipements_en_service(): void
    {
        Equipement::where('id_appui', 3)->delete();
        $a = Equipement::create(['matricule_equipement' => 'CH-001', 'etat' => 'bon', 'en_service' => false, 'id_appui' => 3]);
        $b = Equipement::create(['matricule_equipement' => 'CH-002', 'etat' => 'bon', 'en_service' => false, 'id_appui' => 3]);

        $attribution = $this->aRetirer([3 => 2]);
        $ligne = $attribution->lignesAttributions->first();

        $this->retirer($attribution, ['equipements' => [$ligne->id_ligne_attribution => [$a->id_equipement, $b->id_equipement]]])
            ->assertRedirect(route('enlevements.index'));

        $this->assertTrue($a->fresh()->en_service);
        $this->assertTrue($b->fresh()->en_service);
        $this->assertSame(2, MouvementStock::where('id_attribution', $attribution->id_attribution)->whereNotNull('id_equipement')->count());
    }

    public function test_un_equipement_deja_sorti_fait_echouer_toute_la_sortie(): void
    {
        Equipement::where('id_appui', 3)->delete();
        $libre = Equipement::create(['matricule_equipement' => 'CH-001', 'etat' => 'bon', 'en_service' => false, 'id_appui' => 3]);
        $pris = Equipement::create(['matricule_equipement' => 'CH-002', 'etat' => 'bon', 'en_service' => true, 'id_appui' => 3]);

        $attribution = $this->aRetirer([3 => 2]);
        $ligne = $attribution->lignesAttributions->first();

        $this->retirer($attribution, ['equipements' => [$ligne->id_ligne_attribution => [$libre->id_equipement, $pris->id_equipement]]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, "n'est pas disponible"));

        $this->assertFalse($libre->fresh()->en_service);                 // l'équipement libre n'a pas été pris au passage
        $this->assertSame(0, MouvementStock::where('id_attribution', $attribution->id_attribution)->count());
        $this->assertNull($attribution->fresh()->date_destockage);
    }

    public function test_un_compte_sans_agent_ne_peut_pas_signer_un_mouvement(): void
    {
        $sansAgent = User::factory()->create()->assignRole('gestionnaire-stock');
        $attribution = $this->aRetirer([1 => 20]);
        $stockAvant = Appui::find(1)->quantite_en_stock;

        $this->actingAs($sansAgent);
        $this->retirer($attribution)->assertSessionHas('error', fn ($m) => str_contains($m, "n'est lié à aucun agent"));

        $this->assertSame($stockAvant, Appui::find(1)->quantite_en_stock);
        $this->assertSame(0, MouvementStock::where('id_attribution', $attribution->id_attribution)->count());
    }
}
