<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\TypeAppui;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeAppuiFactory extends Factory
{
    protected $model = TypeAppui::class;

    public function definition(): array
    {
        return [
            'libelle' => $this->faker->word(),
            'est_intrant' => $this->faker->boolean(),
            'est_stockable' => $this->faker->boolean(),
            'id_service' => Service::factory(),
        ];
    }
}
