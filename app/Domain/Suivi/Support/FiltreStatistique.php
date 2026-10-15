<?php

namespace App\Domain\Suivi\Support;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Les filtres des statistiques : département, localité, type d'appui, type
 * d'acteur et période de soumission. Chaque filtre vide est simplement ignoré.
 *
 * Le filtre sait se poser sur n'importe quelle requête qui part de la table
 * « demandes » (appliquer) : toutes les statistiques restreignent ainsi la même
 * population de demandes, de la même façon.
 */
final class FiltreStatistique
{
    public function __construct(
        public readonly ?int $idDepartement = null,
        public readonly ?int $idLocalite = null,
        public readonly ?int $idTypeAppui = null,
        public readonly ?int $idTypeActeur = null,
        public readonly ?Carbon $du = null,
        public readonly ?Carbon $au = null,
    ) {}

    /** Les règles de validation des paramètres d'URL des filtres. */
    public static function regles(): array
    {
        return [
            'departement' => 'nullable|integer|exists:departements,id_departement',
            'localite' => 'nullable|integer|exists:localites,id_localite',
            'type_appui' => 'nullable|integer|exists:types_appuis,id_type_appui',
            'type_acteur' => 'nullable|integer|exists:types_acteurs,id_type_acteur',
            'du' => 'nullable|date',
            'au' => 'nullable|date|after_or_equal:du',
        ];
    }

    /** Construit le filtre à partir des paramètres déjà validés. */
    public static function depuis(array $valeurs): self
    {
        $entier = fn (string $cle) => filled($valeurs[$cle] ?? null) ? (int) $valeurs[$cle] : null;
        $date = fn (string $cle) => filled($valeurs[$cle] ?? null) ? Carbon::parse($valeurs[$cle]) : null;

        return new self(
            idDepartement: $entier('departement'),
            idLocalite: $entier('localite'),
            idTypeAppui: $entier('type_appui'),
            idTypeActeur: $entier('type_acteur'),
            du: $date('du')?->startOfDay(),
            au: $date('au')?->endOfDay(),
        );
    }

    public function estVide(): bool
    {
        return $this->parametres() === [];
    }

    /** Les filtres actifs sous forme de paramètres d'URL (pour garder les filtres d'une page à l'autre). */
    public function parametres(): array
    {
        return array_filter([
            'departement' => $this->idDepartement,
            'localite' => $this->idLocalite,
            'type_appui' => $this->idTypeAppui,
            'type_acteur' => $this->idTypeActeur,
            'du' => $this->du?->toDateString(),
            'au' => $this->au?->toDateString(),
        ], fn ($valeur) => $valeur !== null);
    }

    /**
     * Restreint une requête sur « demandes » aux demandes qui passent les filtres.
     *
     * Les filtres par localité, département et type d'appui passent par
     * « WHERE EXISTS (sous-requête) » plutôt que par une jointure : une demande
     * qui couvre trois localités n'est ainsi comptée qu'une fois.
     *
     * @template T of Builder
     *
     * @param  T  $requete
     * @return T
     */
    public function appliquer(Builder $requete): Builder
    {
        return $requete
            ->when($this->idTypeActeur, fn ($q) => $q->where('demandes.id_type_acteur', $this->idTypeActeur))
            ->when($this->du, fn ($q) => $q->where('demandes.date_soummission', '>=', $this->du))
            ->when($this->au, fn ($q) => $q->where('demandes.date_soummission', '<=', $this->au))
            ->when($this->idLocalite, fn ($q) => $q->whereExists(fn ($sous) => $sous->select(DB::raw(1))
                ->from('demandes_localites')
                ->whereColumn('demandes_localites.id_demande', 'demandes.id_demande')
                ->where('demandes_localites.id_localite', $this->idLocalite)))
            ->when($this->idDepartement, fn ($q) => $q->whereExists(fn ($sous) => $sous->select(DB::raw(1))
                ->from('demandes_localites')
                ->join('localites', 'localites.id_localite', '=', 'demandes_localites.id_localite')
                ->whereColumn('demandes_localites.id_demande', 'demandes.id_demande')
                ->where('localites.id_departement', $this->idDepartement)))
            ->when($this->idTypeAppui, fn ($q) => $q->whereExists(fn ($sous) => $sous->select(DB::raw(1))
                ->from('lignes_demandes')
                ->whereColumn('lignes_demandes.id_demande', 'demandes.id_demande')
                ->where('lignes_demandes.id_type_appui', $this->idTypeAppui)));
    }
}
