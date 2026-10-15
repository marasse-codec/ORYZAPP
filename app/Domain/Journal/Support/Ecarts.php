<?php

namespace App\Domain\Journal\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Calcule ce qui a changé entre deux états d'une même chose, pour le journal.
 *
 * Le résultat a la forme [champ => ['avant' => …, 'apres' => …]] et ne contient
 * que les champs réellement modifiés. Les dates de création/mise à jour sont
 * ignorées : elles changent à chaque enregistrement et n'apprennent rien.
 */
class Ecarts
{
    private const CHAMPS_IGNORES = ['created_at', 'updated_at', 'password', 'remember_token'];

    /**
     * @param  array<string, mixed>  $avant
     * @param  array<string, mixed>  $apres
     * @return array<string, array{avant: mixed, apres: mixed}>
     */
    public static function entre(array $avant, array $apres): array
    {
        $ecarts = [];

        foreach (array_unique(array_merge(array_keys($avant), array_keys($apres))) as $champ) {
            if (in_array($champ, self::CHAMPS_IGNORES, true)) {
                continue;
            }

            $ancien = $avant[$champ] ?? null;
            $nouveau = $apres[$champ] ?? null;

            // Comparaison sur le texte : 2 et « 2.00 » venant de la base ne sont pas un changement.
            if (self::normaliser($ancien) !== self::normaliser($nouveau)) {
                $ecarts[$champ] = ['avant' => $ancien, 'apres' => $nouveau];
            }
        }

        return $ecarts;
    }

    /**
     * Les écarts d'un modèle qu'on vient de remplir (fill) mais pas encore
     * enregistré : à appeler AVANT save(), tant que Laravel garde l'état d'origine.
     *
     * @return array<string, array{avant: mixed, apres: mixed}>
     */
    public static function duModele(Model $modele): array
    {
        $modifies = $modele->getDirty();

        return self::entre(
            array_intersect_key($modele->getRawOriginal(), $modifies),
            $modifies,
        );
    }

    private static function normaliser(mixed $valeur): ?string
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (is_bool($valeur)) {
            return $valeur ? '1' : '0';
        }

        if (is_array($valeur)) {
            return json_encode($valeur);
        }

        if (is_numeric($valeur)) {
            return rtrim(rtrim(number_format((float) $valeur, 4, '.', ''), '0'), '.');
        }

        return (string) $valeur;
    }
}
