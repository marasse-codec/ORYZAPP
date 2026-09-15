<?php

namespace App\Enums;

enum CategorieActeurEnum: int
{
    case producteur = 1;
    case transformateur = 2;
    case distributeur = 3;

    // Tu peux définir des méthodes directement dans l'enum !
    public function libelle(): string
    {
        return match ($this) {
            self::producteur => 'Producteur',
            self::transformateur => 'Transformateur',
            self::distributeur => 'Distributeur',
        };
    }
}
