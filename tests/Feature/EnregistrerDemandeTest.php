<?php

use App\Models\Demande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnregistrerDemandeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demande_can_be_created(): void
    {
        $demande = Demande::factory()->create();

        $this->assertDatabaseHas('demandes', [
            'id_demande' => $demande->id_demande,
        ]);
    }
}
