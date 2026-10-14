<?php

namespace Tests\Feature;

use App\Domain\Stock\Models\Appui;
use App\Domain\Suivi\Exports\StocksExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** Chaque service télécharge l'état des stocks des appuis qu'il fournit, et d'eux seuls. */
class ExportStocksTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    private function agentDeService(): User
    {
        return User::role('agent-service')->whereNotNull('id_service')->firstOrFail();
    }

    public function test_lagent_de_service_ne_voit_que_les_appuis_de_son_service(): void
    {
        $agent = $this->agentDeService();
        $this->actingAs($agent);

        $export = new StocksExport();
        $appuis = $export->collection();

        $this->assertNotEmpty($appuis);
        $this->assertTrue($appuis->every(fn (Appui $a) => $a->typeAppui->id_service === $agent->id_service));
        $this->assertLessThan(Appui::count(), $appuis->count());

        // Demander un autre service n'y change rien.
        $autre = Appui::with('typeAppui')->get()->first(fn ($a) => $a->typeAppui->id_service !== $agent->id_service)->typeAppui->id_service;
        $this->assertSame($appuis->count(), (new StocksExport($autre))->collection()->count());

        foreach ($appuis as $appui) {
            $this->assertCount(count($export->headings()), $export->map($appui));
        }
    }

    public function test_le_stock_engage_se_deduit_du_disponible(): void
    {
        $agent = $this->agentDeService();
        $appui = Appui::whereHas('typeAppui', fn ($q) => $q->where('id_service', $agent->id_service))
            ->whereHas('typeAppui', fn ($q) => $q->where('est_intrant', true))->firstOrFail();
        $appui->update(['quantite_en_stock' => 100]);
        $this->attribuer($this->demandeSoumise(), $this->seance(), [$appui->id_appui => 30]);

        $this->actingAs($agent);
        $export = new StocksExport(null, ['appui', 'stock', 'engagee', 'disponible']);
        $ligne = $export->map($export->collection()->firstWhere('id_appui', $appui->id_appui));

        $this->assertSame([$appui->libelle, 100, 30, 70], $ligne);
    }

    public function test_les_routes_et_les_droits(): void
    {
        // Le service et le magasin téléchargent ; le DP et la commission n'y ont pas accès.
        $this->actingAs($this->agentDeService())->get(route('editions.stocks.index'))->assertOk();
        $this->get(route('editions.stocks.export', ['colonnes' => ['appui', 'stock']]))
            ->assertOk()->assertHeader('content-disposition');

        $this->actingAs(User::role('gestionnaire-stock')->first())->get(route('editions.stocks.index'))->assertOk();
        $this->actingAs(User::role('admin')->first())->get(route('editions.stocks.index', ['id_service' => 1]))->assertOk();

        $this->actingAs(User::role('dp')->first())->get(route('editions.stocks.index'))->assertForbidden();
        $this->actingAs(User::role('agent-commission')->first())->get(route('editions.stocks.export'))->assertForbidden();
    }
}
