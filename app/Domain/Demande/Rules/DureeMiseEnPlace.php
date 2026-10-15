<?php

namespace App\Domain\Demande\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * La fin de mise en place doit tomber au moins N mois après son début.
 * Exemple : début le 15/01, durée minimale 3 mois → fin le 15/04 au plus tôt.
 */
class DureeMiseEnPlace implements ValidationRule
{
    public function __construct(
        private readonly ?string $debut,
        private readonly int $moisMinimum,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $debut = Carbon::parse((string) $this->debut)->startOfDay();
            $fin = Carbon::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return; // une date illisible est déjà signalée par la règle « date »
        }

        if ($this->debut === null || $this->debut === '') {
            return; // le début manque : c'est son propre message qui le dit
        }

        $finMinimale = $debut->copy()->addMonthsNoOverflow($this->moisMinimum);

        if ($fin->lt($finMinimale)) {
            $fail("La fin de mise en place doit être au moins {$this->moisMinimum} mois après le début (au plus tôt le {$finMinimale->format('d/m/Y')}).");
        }
    }
}
