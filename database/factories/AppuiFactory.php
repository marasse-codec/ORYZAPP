<?php

namespace Database\Factories;

use App\Domain\Referentiel\Models\TypeAppui;
use App\Domain\Referentiel\Models\UniteDosage;
use App\Domain\Stock\Models\Appui;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppuiFactory extends Factory
{
    protected $model = Appui::class;

    public function definition(): array
    {
        return [
            'libelle' => $this->faker->word(),
            'id_type_appui' => TypeAppui::factory(),
            'id_unite' => UniteDosage::factory(),
            'quantite_en_stock' => $this->faker->numberBetween(10, 20),
            'seuil_alerte' => 0,
            'cout_unitaire' => $this->faker->numberBetween(500, 50000),
            'dose_ha' => $this->faker->randomFloat(3, 0.01, 2),
        ];
    }
}
