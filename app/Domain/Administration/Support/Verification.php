<?php

namespace App\Domain\Administration\Support;

/**
 * Le résultat d'un contrôle de l'écran « Santé du système » : ce qu'on a vérifié,
 * son état (ok, alerte, erreur, info) et une phrase d'explication.
 */
final class Verification
{
    public const OK = 'ok';

    public const ALERTE = 'alerte';

    public const ERREUR = 'erreur';

    public const INFO = 'info';

    public function __construct(
        public readonly string $libelle,
        public readonly string $etat,
        public readonly string $detail,
    ) {}

    /** La couleur du badge Filament qui correspond à l'état. */
    public function couleur(): string
    {
        return match ($this->etat) {
            self::OK => 'success',
            self::ALERTE => 'warning',
            self::ERREUR => 'danger',
            default => 'gray',
        };
    }

    public function libelleEtat(): string
    {
        return match ($this->etat) {
            self::OK => 'Bon',
            self::ALERTE => 'À surveiller',
            self::ERREUR => 'Problème',
            default => 'Information',
        };
    }
}
