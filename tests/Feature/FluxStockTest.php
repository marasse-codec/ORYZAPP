<?php

namespace Tests\Feature;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Approvisionnement;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Entrées de stock (approvisionnement) et restitution d'équipements. */
class FluxStockTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    private User $magasinier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();

        $this->magasinier = User::role('gestionnaire-stock')->first();
        $this->actingAs($this->magasinier);
    }

    public function test_un_approvisionnement_alimente_consommables_et_equipements(): void
    {
        $stock1 = Appui::find(1)->quantite_en_stock;
        $stock3 = Appui::find(3)->quantite_en_stock;

        $this->post(route('approvisionnements.store'), [
            'date_approvisionnement' => today()->toDateString(),
            'reference_commande' => 'BC-2026-001',
            'origine' => 'Fournisseur test',
            'lignes' => [
                ['id_appui' => 1, 'quantite_ajoutee' => 30],
                ['id_appui' => 3, 'quantite_ajoutee' => 2, 'matricules' => ['CH-100', 'CH-101']],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertEquals($stock1 + 30, Appui::find(1)->quantite_en_stock);
        $this->assertEquals($stock3 + 2, Appui::find(3)->quantite_en_stock);

        $appro = Approvisionnement::where('reference_commande', 'BC-2026-001')->sole();

        // Le consommable : un mouvement global, daté, signé par l'agent (pas par l'identifiant du compte).
        $global = MouvementStock::where('id_approvisionnement', $appro->id_approvisionnement)->where('id_appui', 1)->sole();
        $this->assertEquals(30, $global->quantite);
        $this->assertSame($this->magasinier->agent->id_agent, $global->id_agent);
        $this->assertSame(today()->toDateString(), $global->date_mouvement->toDateString());

        // Le matériel : un équipement et un mouvement par matricule.
        $this->assertSame(2, Equipement::whereIn('matricule_equipement', ['CH-100', 'CH-101'])->count());
        $this->assertSame(2, MouvementStock::where('id_approvisionnement', $appro->id_approvisionnement)->whereNotNull('id_equipement')->count());

        $trace = JournalAction::where('action', ActionJournalEnum::approvisionnementEnregistre->value)->sole();
        $this->assertTrue($trace->cible->is($appro));
        $this->assertSame($this->magasinier->id, $trace->user_id);
    }

    public function test_un_matricule_en_double_annule_tout_lapprovisionnement(): void
    {
        $stock1 = Appui::find(1)->quantite_en_stock;
        $existant = Equipement::value('matricule_equipement');

        $this->post(route('approvisionnements.store'), [
            'date_approvisionnement' => today()->toDateString(),
            'lignes' => [
                ['id_appui' => 1, 'quantite_ajoutee' => 30],
                ['id_appui' => 3, 'quantite_ajoutee' => 1, 'matricules' => [$existant]],
            ],
        ])->assertSessionHasErrors();

        $this->assertSame($stock1, Appui::find(1)->quantite_en_stock);
        $this->assertSame(0, Approvisionnement::count());
        $this->assertSame(0, JournalAction::count());
    }

    public function test_une_restitution_rend_lequipement_disponible(): void
    {
        $equipement = Equipement::where('id_appui', 3)->first();
        $attribution = $this->sortirEquipement($equipement);
        $stockAvant = Appui::find(3)->quantite_en_stock;

        $this->post(route('equipements.retour.store'), [
            'retours' => [['id_equipement' => $equipement->id_equipement, 'etat' => 'Abîmé']],
        ])->assertRedirect(route('equipements.index'));

        $equipement->refresh();
        $this->assertFalse($equipement->en_service);        // de nouveau attribuable
        $this->assertSame('Abîmé', $equipement->etat);

        // Le stock remonte d'une unité, et le retour laisse son mouvement, signé du magasinier.
        $this->assertEquals($stockAvant + 1, Appui::find(3)->quantite_en_stock);
        $mouvement = MouvementStock::where('id_equipement', $equipement->id_equipement)->where('id_type_mouvement_stock', TypeMouvementStock::idDe(TypeMouvementStockEnum::RETOUR))->sole();
        $this->assertSame($this->magasinier->agent->id_agent, $mouvement->id_agent);
        $this->assertSame($attribution->id_attribution, $mouvement->id_attribution);

        $trace = JournalAction::where('action', ActionJournalEnum::equipementRestitue->value)->sole();
        $this->assertTrue($trace->cible->is($equipement));
    }
}
