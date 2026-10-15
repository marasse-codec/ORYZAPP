<?php

namespace App\Domain\Administration\Enums;

/**
 * Les paramètres généraux que l'administrateur peut régler lui-même.
 *
 * Chaque paramètre a une clé (stockée en base), un libellé, une valeur par défaut
 * (utilisée tant que l'administrateur ne l'a pas changée) et une nature : un nombre
 * entier ou un oui/non. Ajouter un paramètre = ajouter un cas ici, rien d'autre.
 */
enum ParametreEnum: string
{
    // --- Mots de passe
    case motDePasseLongueurMin = 'mot_de_passe_longueur_min';
    case motDePasseMajusculesMinuscules = 'mot_de_passe_majuscules_minuscules';
    case motDePasseChiffres = 'mot_de_passe_chiffres';
    case motDePasseSymboles = 'mot_de_passe_symboles';

    // --- Connexion
    case connexionTentativesMax = 'connexion_tentatives_max';

    // --- Rappels du lundi (en jours)
    case rappelBonsJours = 'rappel_bons_jours';
    case rappelBrouillonsJours = 'rappel_brouillons_jours';
    case rappelSeancesJours = 'rappel_seances_jours';

    public function libelle(): string
    {
        return match ($this) {
            self::motDePasseLongueurMin => 'Longueur minimale du mot de passe',
            self::motDePasseMajusculesMinuscules => 'Exiger majuscules et minuscules',
            self::motDePasseChiffres => 'Exiger au moins un chiffre',
            self::motDePasseSymboles => 'Exiger au moins un symbole (! ? @ …)',
            self::connexionTentativesMax => 'Tentatives de connexion avant blocage temporaire',
            self::rappelBonsJours => 'Rappeler un bon non retiré après (jours)',
            self::rappelBrouillonsJours => 'Rappeler un brouillon oublié après (jours)',
            self::rappelSeancesJours => 'Rappeler une séance restée ouverte après (jours)',
        };
    }

    /** Le groupe sous lequel le paramètre s'affiche dans l'écran d'administration. */
    public function groupe(): string
    {
        return match ($this) {
            self::motDePasseLongueurMin, self::motDePasseMajusculesMinuscules,
            self::motDePasseChiffres, self::motDePasseSymboles => 'Mots de passe',
            self::connexionTentativesMax => 'Connexion',
            default => 'Rappels automatiques',
        };
    }

    /** Vrai pour un paramètre oui/non, faux pour un nombre. */
    public function estOuiNon(): bool
    {
        return in_array($this, [self::motDePasseMajusculesMinuscules, self::motDePasseChiffres, self::motDePasseSymboles], true);
    }

    /** La valeur tant que l'administrateur n'a rien changé. */
    public function defaut(): int|bool
    {
        return match ($this) {
            self::motDePasseLongueurMin => 8,
            self::motDePasseMajusculesMinuscules => true,
            self::motDePasseChiffres => true,
            self::motDePasseSymboles => false,
            self::connexionTentativesMax => 5,
            self::rappelBonsJours => 14,
            self::rappelBrouillonsJours => 7,
            self::rappelSeancesJours => 30,
        };
    }

    /** Les bornes acceptées pour un nombre : on évite un mot de passe de 1 caractère ou un rappel à 0 jour. */
    public function bornes(): array
    {
        return match ($this) {
            self::motDePasseLongueurMin => [8, 64],
            self::connexionTentativesMax => [3, 20],
            default => [1, 365],
        };
    }
}
