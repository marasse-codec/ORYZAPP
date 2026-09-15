<?php

namespace App\Enums;

enum TypeCultureEnum: int
{
    case plateau = 1;
    case basFond = 2;
    case irriguee = 3;

    // Tu peux définir des méthodes directement dans l'enum !
    public function libelle(): string
    {
        return match ($this) {
            self::plateau => 'Plateau',
            self::basFond => 'Bas Fond',
            self::irriguee => 'Irriguée',
        };
    }
}
