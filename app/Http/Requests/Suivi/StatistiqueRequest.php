<?php

namespace App\Http\Requests\Suivi;

use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Suivi\Support\FiltreStatistique;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Les paramètres communs aux pages de statistiques : la campagne choisie et les
 * filtres. L'accès lui-même est contrôlé par la route (permissions).
 */
class StatistiqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'annee' => 'nullable|integer|min:2000|max:2100',
            'campagne' => 'nullable|integer|exists:campagnes,id_campagne',
            // Comparaison : les campagnes cochées.
            'campagnes' => 'nullable|array',
            'campagnes.*' => 'integer|exists:campagnes,id_campagne',
        ] + FiltreStatistique::regles();
    }

    /**
     * Le cycle à afficher. On choisit d'abord l'année, puis un de ses cycles :
     *  - un cycle choisi (et de l'année choisie, s'il y en a une) ;
     *  - sinon, pour une année choisie : son cycle en cours, à défaut son dernier cycle ;
     *  - sinon, le cycle en cours.
     */
    public function campagne(): ?Campagne
    {
        $annee = $this->filled('annee') ? $this->integer('annee') : null;
        $choisie = $this->filled('campagne') ? Campagne::find($this->integer('campagne')) : null;

        if ($choisie && ($annee === null || $choisie->annee() === $annee)) {
            return $choisie;
        }

        if ($annee !== null) {
            $enCours = Campagne::enCours();

            return $enCours?->annee() === $annee ? $enCours : Campagne::deLAnnee($annee)->get()->last();
        }

        return Campagne::enCours();
    }

    /** L'année choisie (celle du cycle affiché si aucune n'est choisie). */
    public function annee(?Campagne $campagne): ?int
    {
        return $this->filled('annee') ? $this->integer('annee') : $campagne?->annee();
    }

    public function filtre(): FiltreStatistique
    {
        return FiltreStatistique::depuis($this->validated());
    }
}
