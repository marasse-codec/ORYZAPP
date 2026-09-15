<?php

namespace Database\Factories;

use App\Models\TypeSystemeProduction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeSystemProductionFactory extends Factory
{
    protected $model = TypeSystemeProduction::class;

    public function definition(): array
    {
        return [
            'lib_type_systeme_production' => $this->faker->word(),
        ];
    }
}
