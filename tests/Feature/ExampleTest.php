<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** La racine sert la vitrine publique, qui lit les référentiels. */
    public function test_la_vitrine_publique_repond(): void
    {
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);

        $this->get('/')->assertOk();
    }
}
