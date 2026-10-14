<?php

namespace Database\Factories;

use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\LigneDemande;
use App\Domain\Referentiel\Models\TypeAppui;

use Illuminate\Database\Eloquent\Factories\Factory;

class LigneDemandeFactory extends Factory
{
    protected $model = LigneDemande::class;

    public function definition(): array
    {
        return [
            'id_demande' => Demande::factory(),
            'id_type_appui' => TypeAppui::factory(),
            'quantite_demandee' => $this->faker->numberBetween(1, 50),
        ];
    }
}
