<?php

namespace Database\Factories;

use App\Models\Appui;
use App\Models\Demande;
use App\Models\LigneDemande;

use Illuminate\Database\Eloquent\Factories\Factory;

class LigneDemandeFactory extends Factory
{
    protected $model = LigneDemande::class;

    public function definition(): array
    {
        return [
            'id_demande' => Demande::factory(),
            'id_appui' => Appui::factory(),
            'quantite_demandee' => $this->faker->numberBetween(1,10)
        ];
    }
}
