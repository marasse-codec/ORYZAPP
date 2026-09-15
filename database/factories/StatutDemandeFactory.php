<?php

namespace Database\Factories;

use App\Models\StatutDemande;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatutDemandeFactory extends Factory
{
    protected $model = StatutDemande::class;

    public function definition(): array
    {
        return [
            'lib_statut_demande' => $this->faker->word()
        ];
    }
}
