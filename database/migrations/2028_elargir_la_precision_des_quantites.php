<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les quantités passent en DECIMAL(16,4) partout.
 *
 * L'unité de dosage n'est pas contrôlée : quelqu'un qui demande 400 g d'un produit compté
 * en tonnes doit pouvoir écrire 0,0004. Avec deux décimales (et un entier pour les
 * mouvements de stock), la valeur était arrondie ou tronquée sans prévenir.
 *
 * Concerné : toute colonne qui porte une quantité de produit, y compris la dose à l'hectare.
 * Inchangés : les prix (12,2), les remises (5,2) et les superficies (10,2), qui sont des
 * montants et des surfaces, pas des quantités.
 */
return new class extends Migration
{
    /** table => [colonne => [nullable, défaut]] */
    private const COLONNES = [
        'appuis' => ['quantite_en_stock' => [false, 0], 'seuil_alerte' => [false, 0], 'dose_ha' => [false, null]],
        'lignes_demandes' => ['quantite_demandee' => [true, null]],
        'lignes_attributions' => ['quantite_attribuee' => [false, null]],
        'mouvements_stocks' => ['quantite' => [false, null]],
    ];

    /** Précision d'origine, pour revenir en arrière : colonne => [total, décimales] ou null = entier. */
    private const ANCIENNES = [
        'dose_ha' => [12, 3],
        'quantite' => null,
    ];

    /**
     * SQLite (base des tests) est à typage dynamique : il ne borne ni les décimales ni les entiers, et
     * sait déjà garder 0,0004 dans ces colonnes. Il n'y a rien à convertir, et il ne sait pas le faire.
     */
    private function sansEffet(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    public function up(): void
    {
        if ($this->sansEffet()) {
            return;
        }

        $this->convertir(fn (Blueprint $t, string $colonne) => $t->decimal($colonne, 16, 4));
    }

    public function down(): void
    {
        if ($this->sansEffet()) {
            return;
        }

        // Retour en arrière : la précision perdue l'est pour de bon (arrondi à l'ancienne échelle).
        foreach (self::COLONNES as $table => $colonnes) {
            foreach (array_keys($colonnes) as $colonne) {
                $decimales = array_key_exists($colonne, self::ANCIENNES) ? (self::ANCIENNES[$colonne][1] ?? 0) : 2;
                DB::table($table)->whereNotNull($colonne)->update([$colonne => DB::raw("ROUND({$colonne}, {$decimales})")]);
            }
        }

        $this->convertir(function (Blueprint $t, string $colonne) {
            $ancienne = array_key_exists($colonne, self::ANCIENNES) ? self::ANCIENNES[$colonne] : [12, 2];

            return $ancienne === null ? $t->integer($colonne) : $t->decimal($colonne, $ancienne[0], $ancienne[1]);
        });
    }

    private function convertir(callable $definir): void
    {
        foreach (self::COLONNES as $table => $colonnes) {
            Schema::table($table, function (Blueprint $t) use ($colonnes, $definir) {
                foreach ($colonnes as $colonne => [$nullable, $defaut]) {
                    $definition = $definir($t, $colonne);

                    if ($nullable) {
                        $definition->nullable();
                    }

                    if ($defaut !== null) {
                        $definition->default($defaut);
                    }

                    $definition->change();
                }
            });
        }
    }
};
