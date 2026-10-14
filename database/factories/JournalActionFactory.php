<?php

namespace Database\Factories;

use App\Domain\Journal\Models\JournalAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalActionFactory extends Factory
{
    protected $model = JournalAction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->word(),
        ];
    }
}
