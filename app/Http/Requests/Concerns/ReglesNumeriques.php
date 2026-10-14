<?php

namespace App\Http\Requests\Concerns;

use App\Domain\Stock\Models\Appui;
use Closure;

/**
 * Les règles de validation des nombres, définies une seule fois pour que chaque formulaire
 * accepte exactement ce que la base sait garder (cf. migrations) : ni décimale arrondie en
 * silence, ni valeur trop grande pour la colonne.
 *
 *  - quantité   DECIMAL(16,4)  produit en stock, demandé, attribué, seuil, dose à l'hectare
 *  - prix       DECIMAL(12,2)  coûts unitaires et montants
 *  - superficie DECIMAL(10,2)  hectares
 *  - remise     DECIMAL(5,2)   pourcentage de 0 à 100
 *
 * Les équipements se comptent à l'unité (un matricule par pièce) : voir entierPourEquipement().
 */
final class ReglesNumeriques
{
    /** @param  bool  $strictementPositive  vrai pour une quantité qu'on déplace (attribuée, ajoutée) : zéro n'a pas de sens */
    public static function quantite(bool $strictementPositive = false): array
    {
        return ['numeric', $strictementPositive ? 'min:0.0001' : 'min:0', 'decimal:0,4', 'max:999999999999.9999'];
    }

    public static function prix(): array
    {
        return ['numeric', 'min:0', 'decimal:0,2', 'max:9999999999.99'];
    }

    public static function superficie(): array
    {
        return ['numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'];
    }

    public static function remise(): array
    {
        return ['numeric', 'min:0', 'max:100', 'decimal:0,2'];
    }

    /**
     * Un équipement (matériel, non intrant) se compte à l'unité. La règle lit l'appui de la ligne
     * à côté de la quantité : « lignes.2.quantite_attribuee » regarde « lignes.2.id_appui ».
     */
    public static function entierPourEquipement(): Closure
    {
        return function (string $attribut, mixed $valeur, Closure $echec) {
            $idAppui = request()->input(preg_replace('/\.[^.]+$/', '.id_appui', $attribut));

            if (blank($idAppui) || blank($valeur) || ! is_numeric($valeur)) {
                return;
            }

            $appui = Appui::with('typeAppui')->find($idAppui);

            if ($appui && ! $appui->typeAppui?->est_intrant && floor((float) $valeur) != (float) $valeur) {
                $echec("« {$appui->libelle} » est un équipement : il se compte à l'unité (nombre entier).");
            }
        };
    }
}
