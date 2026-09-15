<?php

namespace Database\Factories;

use App\Models\Appui;
use App\Models\TypeAppui;
use App\Models\UniteAppui;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppuiFactory extends Factory
{
    protected $model = Appui::class;

    public function definition(): array
    {
        return [
            'libelle' => $this->faker->word(),
            'id_type_appui' => TypeAppui::factory(),
            'quantite_en_stock' => $this->faker->numberBetween(10,20),
            'cout_unitaire' => $this->faker->numberBetween(500,50000)
            ];
    }
}
