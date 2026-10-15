<?php

namespace App\Domain\Demande\Support;

use App\Domain\Demande\Rules\DureeMiseEnPlace;
use Illuminate\Support\Carbon;

/**
 * Les règles de saisie d'une demande, partagées par le formulaire unitaire, la
 * modification et la saisie en série (et affichées telles quelles à l'écran) :
 *
 *  - le demandeur est majeur : 18 ans révolus le jour de la saisie ;
 *  - la mise en place dure au moins 3 mois : la fin est au plus tôt 3 mois après le début.
 *
 * On y trouve aussi à quelle étape du formulaire appartient chaque champ, pour
 * ramener l'utilisateur à la bonne étape quand un champ est en erreur.
 */
final class ReglesDemande
{
    public const AGE_MINIMUM = 18;

    public const DUREE_MINIMALE_MOIS = 3;

    /** Les champs de chaque étape du formulaire (le début du nom suffit : « lignes » couvre « lignes.0.id_appui »). */
    private const ETAPES = [
        1 => ['nom', 'date_naissance', 'telephone', 'email', 'nom_structure', 'id_acteur'],
        2 => ['id_type_acteur', 'pieces'],
        3 => ['localites', 'id_campagne', 'periode_debut_mise_en_place', 'periode_fin_mise_en_place', 'superficies', 'selected_departement'],
        4 => ['lignes'],
        5 => ['terrain_visite', 'observations'],
    ];

    /** La date de naissance la plus récente acceptée : aujourd'hui moins 18 ans. */
    public static function dateNaissanceMax(): Carbon
    {
        return today()->subYears(self::AGE_MINIMUM);
    }

    /** Règle de la date de naissance : obligatoire, et 18 ans révolus. */
    public static function dateNaissance(): array
    {
        return ['required', 'date', 'before_or_equal:'.self::dateNaissanceMax()->toDateString()];
    }

    public static function messageMajorite(): string
    {
        return 'Le demandeur doit être majeur : '.self::AGE_MINIMUM.' ans révolus (né au plus tard le '.self::dateNaissanceMax()->format('d/m/Y').').';
    }

    /** Règle de la fin de mise en place : au moins 3 mois après le début saisi. */
    public static function periodeFin(?string $debut): array
    {
        return ['required', 'date', new DureeMiseEnPlace($debut, self::DUREE_MINIMALE_MOIS)];
    }

    /** L'étape du formulaire où se trouve le premier champ en erreur (1 si aucun). */
    public static function etapeEnErreur(array $champsEnErreur): int
    {
        foreach (self::ETAPES as $etape => $champs) {
            foreach ($champsEnErreur as $champ) {
                foreach ($champs as $prefixe) {
                    if ($champ === $prefixe || str_starts_with($champ, $prefixe.'.')) {
                        return $etape;
                    }
                }
            }
        }

        return 1;
    }
}
