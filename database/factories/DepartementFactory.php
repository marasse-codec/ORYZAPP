<?php

namespace Database\Factories;

use App\Domain\Referentiel\Models\Departement;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartementFactory extends Factory
{
    protected $model = Departement::class;

    public function definition(): array
    {
        return [
            'lib_departement' => $this->faker->word(),
        ];
    }
}
