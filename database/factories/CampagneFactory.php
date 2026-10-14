<?php

namespace Database\Factories;

use App\Domain\Referentiel\Models\Campagne;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampagneFactory extends Factory
{
    protected $model = Campagne::class;

    public function definition(): array
    {
        return [
            'lib_campagne' => $this->faker->word(),
            'mois_debut_campagne' => today()->subMonth(),
            'mois_fin_campagne' => today()->addMonths(3),
        ];
    }
}
