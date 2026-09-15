<?php

namespace App\Enums;

enum TypeMouvementStockEnum: int
{
    case ENTREE = 1;
    case SORTIE = 2;
    case RETOUR = 3;
    case AJUSTEMENT = 4;

    // Tu peux définir des méthodes directement dans l'enum !
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
