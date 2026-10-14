<?php

namespace Database\Factories;

use App\Domain\Referentiel\Models\Departement;
use App\Domain\Referentiel\Models\Localite;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocaliteFactory extends Factory
{
    protected $model = Localite::class;

    public function definition(): array
    {
        return [
            'lib_localite' => $this->faker->word(),
            'id_departement' => Departement::factory(),
        ];
    }
}
