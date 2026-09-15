<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Appui;
use App\Models\Localite;
use App\Models\TypeActeur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SNF01 : Affichage d'une liste de 50 demandes en moins de 3 secondes.
     */
    public function test_liste_de_50_demandes_en_moins_de_3_secondes()
    {
        Role::firstOrCreate(['name' => RoleEnum::dp]);

        $delegue = User::factory()->create();
        $delegue->assignRole(RoleEnum::dp);

        $typeActeur = TypeActeur::factory()->create();
        $localite = Localite::factory()->create();
        $appui = Appui::factory()->create();

        // Exécution : Insertion de 50 demandes
        for ($i = 0; $i < 50; $i++) {
            $this->actingAs($delegue)->postJson('/demandes', [
                'id_type_acteur' => $typeActeur->id_type_acteur,
                'nom' => 'Demande '.$i,
                'telephone' => '01000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'id_localite' => $localite->id_localite,
                'debut_periode_mise_en_place' => now()->toDateString(),
                'superficies' => [
                    1 => 5.5,
                ],
                'lignes' => [
                    [
                        'id_appui' => $appui->id_appui,
                        'quantite_demandee' => 10,
                    ],
                ],
                'pieces' => [],
            ]);
        }

        // Mesure : Temps de réponse de l'affichage de la liste
        $depart = microtime(true);

        $response = $this->actingAs($delegue)->get('/demandes');

        $duree = microtime(true) - $depart;

        $response->assertStatus(200);
        $this->assertLessThan(3, $duree, "L'affichage de la liste a pris {$duree}s, au-delà des 3s exigées (SNF01).");
    }

    /**
     * SNF02 : Enregistrement d'une demande accompagnée de 5 pièces jointes en moins de 10 secondes.
     */
    public function test_enregistrement_demande_avec_5_pieces_jointes_en_moins_de_10_secondes()
    {
        Storage::fake('public');

        Role::firstOrCreate(['name' => RoleEnum::dp]);

        $delegue = User::factory()->create();
        $delegue->assignRole(RoleEnum::dp);

        $typeActeur = TypeActeur::factory()->create();
        $localite = Localite::factory()->create();
        $appui = Appui::factory()->create();

        $pieces = [
            1 => UploadedFile::fake()->create('piece1.pdf', 500),
            2 => UploadedFile::fake()->create('piece2.pdf', 500),
            3 => UploadedFile::fake()->create('piece3.pdf', 500),
            4 => UploadedFile::fake()->create('piece4.pdf', 500),
            5 => UploadedFile::fake()->create('piece5.pdf', 500),
        ];

        $depart = microtime(true);

        $response = $this->actingAs($delegue)->postJson('/demandes', [
            'id_type_acteur' => $typeActeur->id_type_acteur,
            'nom' => 'Coopérative Rizicole du Sud',
            'telephone' => '0102030405',
            'id_localite' => $localite->id_localite,
            'debut_periode_mise_en_place' => now()->toDateString(),
            'superficies' => [
                1 => 5.5,
            ],
            'lignes' => [
                [
                    'id_appui' => $appui->id_appui,
                    'quantite_demandee' => 10,
                ],
            ],
            'pieces' => $pieces,
        ]);

        $duree = microtime(true) - $depart;

        $response->assertStatus(302); // ou 201/200 selon la réponse réelle de store()
        $this->assertLessThan(10, $duree, "L'enregistrement a pris {$duree}s, au-delà des 10s exigées (SNF02).");
    }
}