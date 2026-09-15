<?php

namespace Database\Factories;

use App\Models\Acteur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActeurFactory extends Factory
{
    protected $model = Acteur::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->name(),
            'telephone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
        ];
    }
}
