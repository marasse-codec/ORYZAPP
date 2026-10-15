<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Enums\ParametreEnum;
use App\Domain\Administration\Models\Parametre;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

/**
 * Lire et modifier les paramètres généraux.
 *
 * Un paramètre jamais modifié n'a pas de ligne en base : on rend alors sa valeur
 * par défaut. Les valeurs lues sont gardées le temps de la requête, pour ne pas
 * interroger la base à chaque fois.
 */
class ParametreService
{
    /** @var array<string, string|null>|null Les valeurs enregistrées, chargées une seule fois. */
    private ?array $valeurs = null;

    /** La valeur d'un nombre (ex. longueur minimale du mot de passe). */
    public function entier(ParametreEnum $parametre): int
    {
        $valeur = $this->brute($parametre);

        return $valeur === null ? (int) $parametre->defaut() : (int) $valeur;
    }

    /** La valeur d'un oui/non. */
    public function ouiNon(ParametreEnum $parametre): bool
    {
        $valeur = $this->brute($parametre);

        return $valeur === null ? (bool) $parametre->defaut() : $valeur === '1';
    }

    /** La valeur, quelle que soit sa nature (pour l'écran d'administration). */
    public function valeur(ParametreEnum $parametre): int|bool
    {
        return $parametre->estOuiNon() ? $this->ouiNon($parametre) : $this->entier($parametre);
    }

    /** Toutes les valeurs, clé => valeur : de quoi remplir le formulaire. */
    public function tous(): array
    {
        return collect(ParametreEnum::cases())
            ->mapWithKeys(fn (ParametreEnum $p) => [$p->value => $this->valeur($p)])
            ->all();
    }

    /**
     * Enregistre les nouvelles valeurs. Seules celles qui changent sont écrites,
     * et le journal garde l'ancienne et la nouvelle valeur de chacune.
     *
     * @param  array<string, mixed>  $nouvelles  clé => valeur
     */
    public function modifier(array $nouvelles): void
    {
        DB::transaction(function () use ($nouvelles) {
            $changements = [];

            foreach (ParametreEnum::cases() as $parametre) {
                if (! array_key_exists($parametre->value, $nouvelles)) {
                    continue;
                }

                $avant = $this->valeur($parametre);
                $apres = $this->normaliser($parametre, $nouvelles[$parametre->value]);

                if ($avant === $apres) {
                    continue;
                }

                Parametre::updateOrCreate(
                    ['cle' => $parametre->value],
                    ['valeur' => is_bool($apres) ? ($apres ? '1' : '0') : (string) $apres],
                );

                $changements[$parametre->value] = ['avant' => $avant, 'apres' => $apres];
            }

            if ($changements !== []) {
                JournalAction::consigner(ActionJournalEnum::parametresModifies, null, $changements);
            }

            $this->valeurs = null;
        });
    }

    /**
     * La règle de mot de passe tirée des paramètres : longueur minimale et, au
     * choix de l'administrateur, majuscules/minuscules, chiffres, symboles.
     */
    public function reglePassword(): Password
    {
        $regle = Password::min($this->entier(ParametreEnum::motDePasseLongueurMin));

        if ($this->ouiNon(ParametreEnum::motDePasseMajusculesMinuscules)) {
            $regle->mixedCase();
        }

        if ($this->ouiNon(ParametreEnum::motDePasseChiffres)) {
            $regle->numbers();
        }

        if ($this->ouiNon(ParametreEnum::motDePasseSymboles)) {
            $regle->symbols();
        }

        return $regle;
    }

    /** Vérifie et convertit une valeur saisie : un nombre hors bornes est refusé. */
    private function normaliser(ParametreEnum $parametre, mixed $valeur): int|bool
    {
        if ($parametre->estOuiNon()) {
            return filter_var($valeur, FILTER_VALIDATE_BOOLEAN);
        }

        [$min, $max] = $parametre->bornes();
        $nombre = filter_var($valeur, FILTER_VALIDATE_INT);

        if ($nombre === false || $nombre < $min || $nombre > $max) {
            throw new RegleMetierViolee("« {$parametre->libelle()} » doit être un nombre entre {$min} et {$max}.");
        }

        return $nombre;
    }

    /** La valeur brute enregistrée, ou null si l'administrateur n'y a jamais touché. */
    private function brute(ParametreEnum $parametre): ?string
    {
        if ($this->valeurs === null) {
            // Base pas encore installée (première commande d'installation) : valeurs par défaut.
            $this->valeurs = Schema::hasTable('parametres') ? Parametre::pluck('valeur', 'cle')->all() : [];
        }

        return $this->valeurs[$parametre->value] ?? null;
    }
}
