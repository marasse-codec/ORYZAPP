<?php

namespace App\Domain\Suivi\Support;

/**
 * Un objectif de campagne comparé au réalisé.
 *
 * Exemple : « Taux d'attribution : cible 70 %, réalisé 64 % → non atteint ».
 * Pour un taux, plus c'est haut mieux c'est (on vise AU MOINS la cible) ; pour
 * un délai, plus c'est bas mieux c'est (on vise AU PLUS la cible).
 */
final class Objectif
{
    public function __construct(
        public readonly string $libelle,
        public readonly float $cible,
        public readonly ?float $realise,
        public readonly string $unite,
        public readonly bool $auMoins = true,
    ) {}

    /** Vrai si la cible est atteinte, faux sinon, null tant qu'il n'y a rien à mesurer. */
    public function atteint(): ?bool
    {
        if ($this->realise === null) {
            return null;
        }

        return $this->auMoins ? $this->realise >= $this->cible : $this->realise <= $this->cible;
    }

    /** L'écart réalisé − cible (positif = au-dessus de la cible). */
    public function ecart(): ?float
    {
        return $this->realise === null ? null : round($this->realise - $this->cible, 1);
    }

    /** Feu tricolore : vert si atteint, ambre si à moins de 10 % de la cible, rouge sinon. */
    public function ton(): string
    {
        $atteint = $this->atteint();

        if ($atteint === null) {
            return 'gris';
        }

        if ($atteint) {
            return 'vert';
        }

        $proche = $this->auMoins ? $this->realise >= $this->cible * 0.9 : $this->realise <= $this->cible * 1.1;

        return $proche ? 'ambre' : 'rouge';
    }
}
