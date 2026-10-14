<?php

namespace App\Domain\Commission\Enums;

/**
 * Statut d'une attribution. Il n'est jamais persisté : il se déduit de la
 * séance et des dates de l'attribution (cf. Attribution::getStatutAttribute et
 * Attribution::statutSql). La validation, c'est la clôture de la séance.
 */
enum StatutAttributionEnum: int
{
    /** Saisie dans une séance encore ouverte : modifiable, annulable. */
    case enSeance = 1;
    /** Séance clôturée : l'attribution est validée, plus modifiable. */
    case validee = 2;
    /** Résultats publiés : le demandeur peut être informé et le bon imprimé. */
    case publiee = 3;
    /** Appuis retirés au magasin. */
    case retiree = 4;

    public function libelle(): string
    {
        return match ($this) {
            self::enSeance => 'En séance',
            self::validee => 'Validée',
            self::publiee => 'Publiée',
            self::retiree => 'Retirée',
        };
    }

    /** Ton du badge (cf. <x-ui.badge>) : ambre = en cours, bleu = validée, vert = publiée, neutre = clos. */
    public function ton(): string
    {
        return match ($this) {
            self::enSeance => 'ambre',
            self::validee => 'bleu',
            self::publiee => 'vert',
            self::retiree => 'neutre',
        };
    }

    /** Classes Tailwind du badge de statut. */
    public function couleur(): string
    {
        return match ($this) {
            self::enSeance => 'bg-amber-100 text-amber-800',
            self::validee => 'bg-sky-100 text-sky-800',
            self::publiee => 'bg-emerald-100 text-emerald-800',
            self::retiree => 'bg-slate-200 text-slate-700',
        };
    }
}
