<?php

namespace App\Domain\Commission\Enums;

/** Comment un acteur a réglé une partie de son attribution. */
enum ModePaiementEnum: string
{
    case especes = 'especes';
    case mobileMoney = 'mobile_money';
    case virement = 'virement';
    case cheque = 'cheque';

    public function libelle(): string
    {
        return match ($this) {
            self::especes => 'Espèces',
            self::mobileMoney => 'Mobile money',
            self::virement => 'Virement',
            self::cheque => 'Chèque',
        };
    }
}
