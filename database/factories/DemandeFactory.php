<?php

namespace Database\Factories;

use App\Domain\Demande\Models\Acteur;
use App\Domain\Demande\Models\Demande;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Referentiel\Models\TypeActeur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Une demande en brouillon. Son statut se déduit de ses données : voir les états
 * soumise(), rejetee() ; une attribution la fait passer à « reçu attribution ».
 */
class DemandeFactory extends Factory
{
    protected $model = Demande::class;

    public function definition(): array
    {
        return [
            'id_acteur' => Acteur::factory(),
            'id_user' => User::factory(),
            'id_type_acteur' => TypeActeur::factory(),
            'id_campagne' => Campagne::factory(),
            'date_demande' => $this->faker->date(),
        ];
    }

    public function soumise(): static
    {
        return $this->state(fn () => ['date_soummission' => now()]);
    }

    public function rejetee(string $motif = 'Dossier incomplet'): static
    {
        return $this->soumise()->state(fn () => ['motifs_rejet' => $motif]);
    }
}
