<?php

namespace App\Domain\Stock\Enums;

/** Les types de mouvement de stock, désignés par leur code (la clé en base ne se devine pas). */
enum TypeMouvementStockEnum: string
{
    case ENTREE = 'ENTREE';
    case SORTIE = 'SORTIE';
    case RETOUR = 'RETOUR';
    case AJUSTEMENT = 'AJUSTEMENT';

    public function libelle(): string
    {
        return match ($this) {
            self::ENTREE => 'Entrée',
            self::SORTIE => 'Sortie',
            self::RETOUR => 'Retour',
            self::AJUSTEMENT => 'Ajustement',
        };
    }
}
