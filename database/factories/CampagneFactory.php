<?php

namespace Database\Factories;

use App\Models\Campagne;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampagneFactory extends Factory
{
    protected $model = Campagne::class;

    public function definition(): array
    {
        return [
                'lib_campagne' => $this->faker->word(),
                'date_debut_campagne' => $this->faker->date('Y-m-d'),
                'date_fin_campagme' => $this->faker->date('Y-m-d')
            ];
    }
}
