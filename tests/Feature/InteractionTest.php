<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Appui;
use App\Models\Localite;
use App\Models\TypeActeur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SNF03 & SNF04 : Refus de saisie avec message ciblant le champ en erreur.
     */
    public function test_refus_de_saisie_avec_messages_cibles()
    {
        Role::firstOrCreate(['name' => RoleEnum::dp]);

        $delegue = User::factory()->create();
        $delegue->assignRole(RoleEnum::dp);

        $typeActeur = TypeActeur::factory()->create();
        $localite = Localite::factory()->create();
        $appui = Appui::factory()->create();

        $response = $this->actingAs($delegue)
            ->postJson('/demandes', [
                'id_type_acteur' => $typeActeur->id_type_acteur,
                'nom' => 'Coopérative Rizicole du Sud',
                'telephone' => '0102030405',
                'id_localite' => null,
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

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'id_localite',
        ]);
    }
}