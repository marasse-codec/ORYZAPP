<?php

namespace App\Domain\Demande\Enums;

/**
 * Statut d'une demande. Il n'est jamais persisté : il se déduit des dates
 * portées par la demande et son attribution (cf. Demande::getStatutAttribute et Demande::statutSql).
 * Stocker un statut en plus des dates ouvrirait la porte aux deux qui se
 * contredisent.
 */
enum StatutDemandeE: int
{
    case brouillon = 1;
    case soumise = 2;
    case recu_attribution = 3;
    case rejetee = 4;

    public function libelle(): string
    {
        return match ($this) {
            self::brouillon => 'En cours',
            self::soumise => 'Soumise',
            self::recu_attribution => 'Reçu attribution',
            self::rejetee => 'Demande completement rejetée'
        };
    }

    /** Ton du badge et des chiffres (cf. <x-ui.badge>, <x-ui.stat>). */
    public function ton(): string
    {
        return match ($this) {
            self::brouillon => 'neutre',
            self::soumise => 'ambre',
            self::recu_attribution => 'vert',
            self::rejetee => 'rouge',
        };
    }

    /** Classes Tailwind du badge de statut. */
    public function couleur(): string
    {
        return match ($this) {
            self::brouillon => 'bg-orange-100 text-slate-700',
            self::soumise => 'bg-red-100 text-slate-700',
            self::recu_attribution => 'bg-pink-100 text-pink-800',
            self::rejetee => 'bg-black-100 text-pink-800'
        };
    }

    /** Couleur (hexadécimale) du statut dans les graphiques. */
    public function couleurGraphique(): string
    {
        return match ($this) {
            self::brouillon => '#94a3b8',
            self::soumise => '#f59e0b',
            self::recu_attribution => '#1E5E3A',
            self::rejetee => '#e11d48',
        };
    }
}
