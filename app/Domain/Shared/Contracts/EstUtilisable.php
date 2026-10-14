<?php

namespace App\Domain\Shared\Contracts;

/**
 * Un élément de référentiel sait dire s'il est utilisé ailleurs. C'est la règle
 * qui protège les données : un élément utilisé ne se supprime pas, même quand
 * la base, par cascade, le laisserait faire.
 */
interface EstUtilisable
{
    public function estUtilise(): bool;
}
