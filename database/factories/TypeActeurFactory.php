<?php

namespace Database\Factories;

use App\Domain\Referentiel\Models\TypeActeur;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeActeurFactory extends Factory
{
    protected $model = TypeActeur::class;

    public function definition(): array
    {
        return [
            'lib_type_acteur' => $this->faker->jobTitle(),
            'code_type_acteur' => strtoupper($this->faker->unique()->lexify('???')),
        ];
    }
}
