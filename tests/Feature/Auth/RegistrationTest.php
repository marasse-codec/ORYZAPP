<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pas d'inscription publique : les acteurs de la filière ne font que visiter le site,
 * et les comptes des agents sont créés par l'administrateur.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_il_ny_a_pas_dinscription_publique(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com'])->assertNotFound();
    }

    public function test_la_page_de_connexion_ne_propose_pas_de_creer_un_compte(): void
    {
        $this->withoutVite();

        $this->get(route('login'))->assertOk()->assertDontSee('Créer un compte');
    }
}
