<?php

namespace App\Utils;

class Helper
{
    /** Décimales d'une quantité (DECIMAL(16,4)) : 400 g d'un produit compté en tonnes = 0,0004. */
    public const DECIMALES_QUANTITE = 4;

    /** Une quantité ramenée à la précision de la base : les sommes et comparaisons ne dérivent pas. */
    public static function arrondiQuantite(float|int|string|null $valeur): float
    {
        return round((float) $valeur, self::DECIMALES_QUANTITE);
    }

    /** Une quantité à l'affichage : séparateurs français, sans zéros inutiles (5 ; 2,5 ; 0,0004 ; 1 200). */
    public static function quantite(float|int|string|null $valeur): string
    {
        $texte = number_format(self::arrondiQuantite($valeur), self::DECIMALES_QUANTITE, ',', "\u{202F}");

        return rtrim(rtrim($texte, '0'), ',');
    }

    /**
     * Le layout applicatif. Un seul suffit désormais : la barre latérale se
     * compose à partir des permissions du rôle connecté.
     */
    public static function layout(): string
    {
        return 'layouts.aderiz';
    }
}
