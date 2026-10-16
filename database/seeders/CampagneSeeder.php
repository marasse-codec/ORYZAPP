<?php

namespace Database\Seeders;

use App\Domain\Referentiel\Models\Campagne;
use Illuminate\Database\Seeder;

/**
 * Les cycles de l'année en cours (2026) et ceux de l'année à venir (2027).
 *
 * Les cycles de l'année n + 1 sont créés à l'avance : on peut déjà y inscrire
 * des demandes pour préparer la saison suivante. Ils ne deviennent « en cours »
 * qu'à leur date de début (cf. Campagne::enCours()).
 */
class CampagneSeeder extends Seeder
{
    /** Les trois cycles d'une année : [libellé, mois de début, mois de fin]. */
    private const CYCLES = [
        ['Cycle 1', '01-01', '04-01'],
        ['Cycle 2', '04-01', '08-01'],
        ['Cycle 3', '08-01', '12-01'],
    ];

    public function run(): void
    {
        foreach ([2026, 2027] as $annee) {
            foreach (self::CYCLES as [$libelle, $debut, $fin]) {
                Campagne::create([
                    'lib_campagne' => $libelle,
                    'mois_debut_campagne' => "{$annee}-{$debut}",
                    'mois_fin_campagne' => "{$annee}-{$fin}",
                ]);
            }
        }
    }
}
