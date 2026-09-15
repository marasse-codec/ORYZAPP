<?php

namespace Tests\Feature;

use Database\Seeders\DirectionSeeder;
use Database\Seeders\LocaliteSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\TypeActeurSeeder;
use Database\Seeders\TypeAppuiSeeder;
use Database\Seeders\TypePieceSeeder;
use Database\Seeders\TypeSystemeProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** La racine sert la vitrine publique, qui lit les référentiels. */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed([
            DirectionSeeder::class,
            ServiceSeeder::class,
            LocaliteSeeder::class,
            TypeActeurSeeder::class,
            TypePieceSeeder::class,
            TypeSystemeProductionSeeder::class,
            TypeAppuiSeeder::class,
        ]);

        $this->get('/')->assertStatus(200);
    }
}
