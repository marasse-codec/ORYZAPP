<?php

namespace App\Domain\Referentiel\Services;

use App\Domain\Referentiel\Models\TypeActeur;
use App\Domain\Referentiel\Models\TypePiece;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Les pièces exigées des demandeurs : quelle pièce chaque profil (type
 * d'acteur) doit fournir, et si elle est obligatoire ou facultative.
 *
 * Ce rattachement vit dans la table de liaison types_acteurs_types_pieces.
 * L'administration le règle des deux côtés :
 *  - depuis un type de pièce : « qui doit fournir cette pièce ? » ;
 *  - depuis un type d'acteur : « quelles pièces ce profil fournit-il ? ».
 *
 * Le formulaire de demande et la page vitrine lisent cette table : un
 * changement fait ici s'y voit tout de suite, sans toucher au code.
 *
 * Une « exigence » est une ligne de la forme
 * ['id' => identifiant de l'autre côté, 'est_obligatoire' => bool].
 */
class ExigencePieceService
{
    /** @return list<array{id_type_acteur: int, est_obligatoire: bool}> */
    public function profilsDeLaPiece(TypePiece $piece): array
    {
        return $this->lire($piece->typesActeurs(), 'id_type_acteur');
    }

    /** @return list<array{id_type_piece: int, est_obligatoire: bool}> */
    public function piecesDuProfil(TypeActeur $profil): array
    {
        return $this->lire($profil->typesPieces(), 'id_type_piece');
    }

    /**
     * Remplace la liste des profils qui doivent fournir cette pièce.
     *
     * @param  array<int, array{id_type_acteur?: mixed, est_obligatoire?: mixed}>  $lignes
     * @return array<string, array{avant: string, apres: string}> Écart à noter au journal (vide si rien ne change).
     */
    public function fixerProfilsDeLaPiece(TypePiece $piece, array $lignes): array
    {
        return $this->fixer($piece->typesActeurs(), $lignes, 'id_type_acteur', 'profils_concernes');
    }

    /**
     * Remplace la liste des pièces que ce profil doit fournir.
     *
     * @param  array<int, array{id_type_piece?: mixed, est_obligatoire?: mixed}>  $lignes
     * @return array<string, array{avant: string, apres: string}>
     */
    public function fixerPiecesDuProfil(TypeActeur $profil, array $lignes): array
    {
        return $this->fixer($profil->typesPieces(), $lignes, 'id_type_piece', 'pieces_a_fournir');
    }

    /** @return list<array<string, int|bool>> */
    private function lire(BelongsToMany $relation, string $cle): array
    {
        return $relation->get()
            ->map(fn ($element) => [
                $cle => (int) $element->getKey(),
                'est_obligatoire' => (bool) $element->pivot->est_obligatoire,
            ])
            ->values()
            ->all();
    }

    /**
     * Synchronise la table de liaison (ajouts, retraits, obligatoire ↔ facultative)
     * et renvoie l'écart lisible « avant → après ».
     */
    private function fixer(BelongsToMany $relation, array $lignes, string $cle, string $champJournal): array
    {
        $avant = $this->resume($relation);

        // Une ligne vide ou répétée du formulaire ne compte pas ; la dernière l'emporte.
        $attachements = collect($lignes)
            ->filter(fn ($ligne) => filled($ligne[$cle] ?? null))
            ->mapWithKeys(fn ($ligne) => [(int) $ligne[$cle] => ['est_obligatoire' => (bool) ($ligne['est_obligatoire'] ?? false)]])
            ->all();

        $relation->sync($attachements);

        $apres = $this->resume($relation);

        return $avant === $apres ? [] : [$champJournal => ['avant' => $avant, 'apres' => $apres]];
    }

    /** « Agrément (facultative), Liste des membres » : lisible tel quel dans le journal. */
    private function resume(BelongsToMany $relation): string
    {
        $libelles = $relation->get()
            ->map(fn ($element) => ($element->lib_type_piece ?? $element->lib_type_acteur)
                .($element->pivot->est_obligatoire ? '' : ' (facultative)'))
            ->sort()
            ->values();

        return $libelles->isEmpty() ? 'aucune' : $libelles->implode(', ');
    }
}
