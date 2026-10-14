<?php

namespace Tests\Feature;

use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Services\CreationDemandeService;
use App\Domain\Referentiel\Models\Campagne;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Exigences de performance : SNF01 (liste) et SNF02 (enregistrement avec pièces). */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('public');
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    /** SNF01 : affichage d'une liste de 50 demandes en moins de 3 secondes. */
    public function test_liste_de_50_demandes_en_moins_de_3_secondes(): void
    {
        $dp = User::role('dp')->first();
        Demande::factory()->count(50)->soumise()->create([
            'id_user' => $dp->id,
            'id_campagne' => Campagne::first()->id_campagne,
            'id_type_acteur' => 1,
        ]);

        $depart = microtime(true);
        $reponse = $this->actingAs($dp)->get(route('demandes.index'));
        $duree = microtime(true) - $depart;

        $reponse->assertOk();
        $this->assertLessThan(3, $duree, "L'affichage de la liste a pris {$duree}s, au-delà des 3s exigées (SNF01).");
    }

    /** SNF02 : enregistrement d'une demande accompagnée de 5 pièces jointes en moins de 10 secondes. */
    public function test_enregistrement_demande_avec_5_pieces_jointes_en_moins_de_10_secondes(): void
    {
        $this->actingAs(User::role('dp')->first());

        $pieces = [];
        foreach ([1, 2, 3, 4, 5] as $idTypePiece) {
            $pieces[$idTypePiece] = UploadedFile::fake()->create("piece{$idTypePiece}.pdf", 500);
        }

        $depart = microtime(true);
        $demande = app(CreationDemandeService::class)->creer([
            'id_type_acteur' => 1,
            'nom' => 'Coopérative Rizicole du Sud',
            'telephone' => '0102030405',
            'date_naissance' => '1975-05-05',
            'localites' => [1],
            'id_campagne' => Campagne::first()->id_campagne,
            'periode_debut_mise_en_place' => '2026-11-01',
            'periode_fin_mise_en_place' => '2027-01-31',
            'superficies' => [1 => 5.5],
            'lignes' => [['id_type_appui' => 1, 'id_appui' => 1, 'quantite_demandee' => 10]],
        ], $pieces);
        $duree = microtime(true) - $depart;

        $this->assertCount(5, $demande->piecesDemandes);
        $this->assertLessThan(10, $duree, "L'enregistrement a pris {$duree}s, au-delà des 10s exigées (SNF02).");
    }
}
