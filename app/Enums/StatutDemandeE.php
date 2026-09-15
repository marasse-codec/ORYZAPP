<?php

namespace App\Enums;

/**
 * Statut d'une demande. Il n'est jamais persisté : il se déduit des dates
 * portées par la demande et son attribution (cf. Demande::getStatutAttribute).
 * Stocker un statut en plus des dates ouvrirait la porte aux deux qui se
 * contredisent.
 */
enum StatutDemandeE: int
{
    case brouillon = 1;
    case soumise = 2;
    case recu_attribution = 3;
    case rejetee = 4;
    case a_corriger = 5;

    public function libelle(): string
    {
        return match ($this) {
            self::brouillon => 'Brouillon',
            self::soumise => 'Soumise',
            self::a_corriger => 'À corriger',
            self::recu_attribution => 'Reçu attribution',
            self::rejetee => 'Demande completement rejetée'
        };
    }

    /** Classes Tailwind du badge de statut. */
    public function couleur(): string
    {
        return match ($this) {
            self::brouillon => 'bg-orange-100 text-slate-700',
            self::soumise => 'bg-red-100 text-slate-700',
            self::a_corriger => 'bg-yellow-100 text-yellow-800',
            self::recu_attribution => 'bg-pink-100 text-pink-800',
            self::rejetee => 'bg-black-100 text-pink-800'
        };
    }
}
