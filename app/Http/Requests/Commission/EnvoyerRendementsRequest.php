<?php

namespace App\Http\Requests\Commission;

use Illuminate\Foundation\Http\FormRequest;

/** Le retour du DP sur ce que les superficies de sa demande ont produit. */
class EnvoyerRendementsRequest extends FormRequest
{
    /** Le DP qui a émis la demande transmet les rendements de ses superficies ; l'admin peut les saisir à sa place. */
    public function authorize(): bool
    {
        return $this->route('attribution')->rendementsSaisissablesPar($this->user());
    }

    public function rules(): array
    {
        return [
            'rendements' => ['required', 'array'],
            'rendements.*.superficie_recoltee' => ['nullable', 'required_with:rendements.*.production_tonnes', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'rendements.*.production_tonnes' => ['nullable', 'required_with:rendements.*.superficie_recoltee', 'numeric', 'min:0', 'decimal:0,3', 'max:999999999.999'],
        ];
    }

    public function messages(): array
    {
        return [
            'rendements.required' => 'Aucun rendement à enregistrer.',
            'rendements.*.superficie_recoltee.required_with' => 'Indiquez la superficie récoltée en même temps que la production.',
            'rendements.*.production_tonnes.required_with' => 'Indiquez la production en même temps que la superficie récoltée.',
            'rendements.*.superficie_recoltee.numeric' => 'La superficie récoltée doit être un nombre.',
            'rendements.*.production_tonnes.numeric' => 'La production doit être un nombre de tonnes.',
            'rendements.*.superficie_recoltee.min' => 'La superficie récoltée ne peut pas être négative.',
            'rendements.*.production_tonnes.min' => 'La production ne peut pas être négative.',
        ];
    }
}
