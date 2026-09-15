<?php

namespace Database\Factories;

use App\Models\Demande;
use App\Models\SystemeProduction;
use App\Models\TypeSystemeProduction;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemeProductionFactory extends Factory
{
    protected $model = SystemeProduction::class;

    public function definition(): array
    {
        return [
            'superficie' => $this->faker->numberBetween(1,10) ,
            'id_demande' => Demande::factory(),
            'id_type_systeme_production' => TypeSystemeProduction::factory()
        ];
    }
}
