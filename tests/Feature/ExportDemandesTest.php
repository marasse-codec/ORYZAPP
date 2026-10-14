<?php

namespace Tests\Feature;

use App\Domain\Suivi\Exports\DemandesExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreeDonneesDeSuivi;
use Tests\TestCase;

/** L'export Excel des demandes : une ligne par demande, dans le périmètre de l'utilisateur. */
class ExportDemandesTest extends TestCase
{
    use CreeDonneesDeSuivi;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->preparerDonnees();
    }

    public function test_une_ligne_par_demande_avec_superficies_et_statut(): void
    {
        // Les demandes du jeu de démonstration sont datées d'avant : on les écarte par la période.
        $demande = $this->demandeSoumise(['date_demande' => today()]);
        $this->systeme($demande, 1, 12.5);
        $this->systeme($demande, 2, 8);
        $this->ligne($demande, 1);

        $this->actingAs(User::role('admin')->first());
        $export = new DemandesExport(today()->toDateString(), today()->toDateString());
        $lignes = $export->collection();

        $this->assertCount(1, $lignes);
        $ligne = $export->map($lignes->first());
        $this->assertCount(count($export->headings()), $ligne);
        $this->assertContains(20.5, $ligne);
        $this->assertContains('SOUMISE', $ligne);
    }

    public function test_le_dp_ne_voit_que_ses_demandes(): void
    {
        $dp = User::role('dp')->first();
        $mienne = $this->demandeSoumise(['id_user' => $dp->id]);
        $this->demandeSoumise(['id_user' => User::role('admin')->value('id')]);

        $this->actingAs($dp);

        $ids = (new DemandesExport(today()->toDateString(), today()->toDateString()))->collection()->pluck('id_demande')->all();
        $this->assertSame([$mienne->id_demande], $ids);
    }

    public function test_les_routes_repondent(): void
    {
        $this->demandeSoumise();

        $this->actingAs(User::role('admin')->first())
            ->get(route('editions.demandes.index'))->assertOk()->assertSee('Exporter les demandes');

        $this->get(route('stats.export.demandes', ['date_debut' => now()->subYear()->toDateString(), 'date_fin' => now()->toDateString(), 'colonnes' => ['numero', 'statut']]))
            ->assertOk()->assertHeader('content-disposition');
    }
}
