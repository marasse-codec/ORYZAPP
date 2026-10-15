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
            'campagne' => 'nullable|integer|exists:campagnes,id_campagne',
            // Comparaison : les campagnes cochées.
            'campagnes' => 'nullable|array',
            'campagnes.*' => 'integer|exists:campagnes,id_campagne',
        ] + FiltreStatistique::regles();
    }

    /** La campagne demandée, sinon la campagne en cours. */
    public function campagne(): ?Campagne
    {
        return $this->filled('campagne') ? Campagne::find($this->integer('campagne')) : Campagne::enCours();
    }

    public function filtre(): FiltreStatistique
    {
        return FiltreStatistique::depuis($this->validated());
    }
}
