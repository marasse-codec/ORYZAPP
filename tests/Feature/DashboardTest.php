<?php

namespace Tests\Feature;

use App\Models\Appui;
use App\Models\User;
use App\Services\StatistiqueService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Le tableau de bord s'affiche pour chaque rôle, et ses chiffres collent aux données. */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_chaque_role_voit_son_tableau_de_bord(): void
    {
        foreach (User::all() as $user) {
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Tableau de bord');
        }
    }

    public function test_les_etats_couvrent_toutes_les_demandes(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $this->actingAs($admin);

        $etats = app(StatistiqueService::class)->demandesParEtat($admin);

        $this->assertSame(\App\Models\Demande::count(), array_sum($etats));
    }

    public function test_un_appui_sous_son_seuil_declenche_une_alerte(): void
    {
        $appui = Appui::whereHas('typeAppui', fn ($q) => $q->where('est_intrant', true))->firstOrFail();
        $appui->update(['quantite_en_stock' => 2, 'seuil_alerte' => 5]);

        $alertes = app(StatistiqueService::class)->stock()['alertes'];

        $this->assertTrue($alertes->contains(fn ($l) => $l['appui']->is($appui)));
    }
}
