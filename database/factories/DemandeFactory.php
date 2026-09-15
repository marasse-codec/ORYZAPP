<?php

namespace Database\Factories;

use App\Enums\StatutDemandeE;
use App\Models\Acteur;
use App\Models\Demande;
use App\Models\Localite;
use App\Models\TypeActeur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DemandeFactory extends Factory
{
    protected $model = Demande::class;

    public function definition(): array
    {
        return [
            'id_acteur' => Acteur::factory(),
            'date_demande' => $this->faker->date(),
            'debut_periode_mise_en_place' => $this->faker->date(),
            'id_type_acteur' => TypeActeur::factory(),
            'id_localite' => Localite::factory(),
            'motifs_rejet' => null,
            'date_rejet' => null,
            'date_attribution' => null,
            'id_user' => User::factory(),
            'id_statut_demande' => StatutDemandeE::brouillon,
            'id_campagne' => CampagneFactory::factory()
        ];
    }
}
