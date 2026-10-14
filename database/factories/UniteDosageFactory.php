<?php

namespace Database\Factories;

use App\Domain\Referentiel\Models\UniteDosage;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniteDosageFactory extends Factory
{
    protected $model = UniteDosage::class;

    public function definition(): array
    {
        return [
            'code_unite' => $this->faker->unique()->lexify('???'),
            'lib_unite' => $this->faker->word(),
        ];
    }
}
