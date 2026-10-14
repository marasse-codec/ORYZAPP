<?php

namespace Database\Factories;

use App\Domain\Demande\Models\Acteur;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActeurFactory extends Factory
{
    protected $model = Acteur::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->name(),
            // Dix chiffres, unique : la colonne est limitée à 10 caractères.
            'telephone' => $this->faker->unique()->numerify('07########'),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
