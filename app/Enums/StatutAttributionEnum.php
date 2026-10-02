<?php

namespace App\Enums;

/**
 * Statut d'une demande. Il n'est jamais persisté : il se déduit des dates
 * portées par la demande et son attribution (cf. Demande::getStatutAttribute).
 * Stocker un statut en plus des dates ouvrirait la porte aux deux qui se
 * contredisent.
 */
enum StatutAttributionEnum: int
{
    case brouillon = 1;
    case soumise = 2;
    case validee = 3;
    case annulee = 4;

    public function libelle(): string
    {
        return match ($this) {
            self::brouillon => 'En cours',
            self::soumise => 'Soumise',
            self::validee => 'Validee par commission',
            self::annulee => 'Annulee en commission'
        };
    }

    /** Classes Tailwind du badge de statut. */
    public function couleur(): string
    {
        return match ($this) {
            self::brouillon => 'bg-orange-100 text-slate-700',
            self::soumise => 'bg-red-100 text-slate-700',
            self::annulee => 'bg-yellow-100 text-yellow-800',
            self::validee => 'bg-pink-100 text-pink-800',
        };
    }
}
