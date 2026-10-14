<?php

namespace Tests\Feature;

use App\Domain\Commission\Enums\StatutAttributionEnum;
use App\Domain\Stock\Models\Appui;
use App\Domain\Suivi\Exports\AttributionsExport;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** L'export Excel des attributions s'appuie sur le schéma actuel : service via l'appui, statut via les dates. */
class ExportAttributionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lexport_liste_les_lignes_avec_service_et_statut(): void
    {
        Notification::fake();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemonstrationSeeder::class);
        $this->actingAs(User::role('admin')->first());

        $export = new AttributionsExport(now()->subYear()->toDateString(), now()->toDateString());
        $lignes = $export->collection();

        $this->assertNotEmpty($lignes);
        // Le jeu de démonstration couvre tous les statuts déduits : en séance, validée, publiée, retirée.
        $this->assertEqualsCanonicalizing(
            array_map(fn (StatutAttributionEnum $s) => $s->value, StatutAttributionEnum::cases()),
            $lignes->pluck('statut_code')->map(fn ($c) => (int) $c)->unique()->values()->all(),
        );

        // Le service traitant est celui du type de l'appui attribué.
        $ligne = $lignes->firstWhere('appui_libelle', Appui::find(1)->libelle);
        $this->assertSame(Appui::find(1)->typeAppui->service->lib_service, $ligne->service_nom);

        // Chaque ligne se met en forme sans erreur.
        foreach ($lignes as $ligne) {
            $this->assertCount(count($export->headings()), $export->map($ligne));
        }
    }

    public function test_la_route_dexport_repond(): void
    {
        Notification::fake();
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemonstrationSeeder::class);

        $this->actingAs(User::role('admin')->first())
            ->get(route('stats.export.attributions', ['date_debut' => now()->subYear()->toDateString(), 'date_fin' => now()->toDateString()]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
