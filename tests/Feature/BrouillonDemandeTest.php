<?php

namespace Tests\Feature;

use App\Domain\Demande\Models\BrouillonSaisie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrouillonDemandeTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_brouillon_est_enregistre_puis_remplace_et_relu(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('demandes.brouillon.save'), ['donnees' => ['nom' => 'Rakoto', 'step' => 2]])->assertOk();
        $this->actingAs($user)->putJson(route('demandes.brouillon.save'), ['donnees' => ['nom' => 'Rakoto Jean', 'step' => 3]])->assertOk();

        $this->assertSame(1, BrouillonSaisie::where('id_user', $user->id)->count());
        $this->actingAs($user)->getJson(route('demandes.brouillon.show'))
            ->assertOk()->assertJsonPath('donnees.nom', 'Rakoto Jean')->assertJsonPath('donnees.step', 3);
    }

    public function test_le_brouillon_est_propre_a_chaque_utilisateur(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a)->putJson(route('demandes.brouillon.save'), ['donnees' => ['nom' => 'A']])->assertOk();

        $this->actingAs($b)->getJson(route('demandes.brouillon.show'))->assertNoContent();
        $this->actingAs($b)->deleteJson(route('demandes.brouillon.destroy'))->assertNoContent();
        $this->assertDatabaseCount('brouillons_saisies', 1);
    }

    public function test_le_brouillon_peut_etre_supprime_et_exige_une_session(): void
    {
        $user = User::factory()->create();
        $this->putJson(route('demandes.brouillon.save'), ['donnees' => ['nom' => 'X']])->assertRedirect();

        $this->actingAs($user)->putJson(route('demandes.brouillon.save'), ['donnees' => ['nom' => 'X']]);
        $this->actingAs($user)->deleteJson(route('demandes.brouillon.destroy'))->assertNoContent();
        $this->assertDatabaseCount('brouillons_saisies', 0);
    }
}
