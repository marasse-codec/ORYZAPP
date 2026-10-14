<?php

namespace Database\Factories;

use App\Domain\Referentiel\Models\Service;
use App\Domain\Referentiel\Models\TypeAppui;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeAppuiFactory extends Factory
{
    protected $model = TypeAppui::class;

    public function definition(): array
    {
        return [
            'lib_type_appui' => $this->faker->word(),
            'est_intrant' => $this->faker->boolean(),
            'id_service' => Service::factory(),
        ];
    }
}
