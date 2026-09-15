<?php

namespace Database\Factories;

use App\Models\JournalAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalActionFactory extends Factory
{
    protected $model = JournalAction::class;

    public function definition(): array
    {
        return [
            'lib_departement' => $this->faker->word(),
        ];
    }

}
