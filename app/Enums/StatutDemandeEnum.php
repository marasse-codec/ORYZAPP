<?php

/*namespace App\Enums;

/**
 * Statut d'une demande. Il n'est jamais persisté : il se déduit des dates
 * portées par la demande et son attribution (cf. Demande::getStatutAttribute).
 * Stocker un statut en plus des dates ouvrirait la porte aux deux qui se
 * contredisent.
 */
/*enum StatutDemandeEnum: string
{
    case soumise = 'soumise';
    case enInstruction = 'en-instruction';
    case autoriseeCommission = 'autorisee-commission';
    case attribuee = 'attribuee';
    case servie = 'servie';
    case rejetee = 'rejetee';

    public function libelle(): string
    {
        return match ($this) {
            self::soumise => 'Soumise',
            self::enInstruction => 'En instruction',
            self::autoriseeCommission => 'Autorisée pour commission',
            self::attribuee => 'Attribuée',
            self::servie => 'Servie',
            self::rejetee => 'Rejetée',
        };
    }

    /** Classes Tailwind du badge de statut. */
/*public function couleur(): string
{
    return match ($this) {
        self::soumise => 'bg-slate-100 text-slate-700',
        self::enInstruction => 'bg-orange-100 text-orange-800',
        self::autoriseeCommission => 'bg-blue-100 text-blue-800',
        self::attribuee => 'bg-emerald-100 text-emerald-800',
        self::servie => 'bg-green-600 text-white',
        self::rejetee => 'bg-red-100 text-red-800',
    };
}
}
*/
