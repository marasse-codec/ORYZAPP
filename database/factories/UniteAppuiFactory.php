<?php

namespace Database\Factories;

use App\Models\UniteAppui;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniteAppuiFactory extends Factory
{
    protected $model = UniteAppui::class;

    public function definition(): array
    {
        return [
            'lib_unite_appui' => $this->faker->word()
        ];
    }
}
