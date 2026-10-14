<?php

namespace App\Http\Requests\Stock;

use App\Http\Requests\Concerns\ReglesNumeriques;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Un appui du catalogue (création et modification). La quantité en stock n'est pas un champ du formulaire. */
class EnregistrerAppuiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $appui = $this->route('appui');

        return [
            'libelle' => ['required', 'string', 'max:255', Rule::unique('appuis', 'libelle')->ignore($appui?->id_appui, 'id_appui')],
            'id_type_appui' => ['required', 'exists:types_appuis,id_type_appui'],
            'id_unite' => ['required', 'exists:unites_dosages,id_unite'],
            'dose_ha' => ['required', ...ReglesNumeriques::quantite()],
            'cout_unitaire' => ['nullable', ...ReglesNumeriques::prix()],
            'seuil_alerte' => ['nullable', ...ReglesNumeriques::quantite()],
            'systemes' => ['nullable', 'array'],
            'systemes.*' => ['exists:types_systemes_productions,id_type_systeme_production'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => "Le libellé de l'appui est obligatoire.",
            'libelle.unique' => 'Un appui porte déjà ce libellé.',
            'id_type_appui.required' => "Choisissez le type d'appui.",
            'id_unite.required' => "Choisissez l'unité de dosage.",
            'dose_ha.required' => 'La dose par hectare est obligatoire.',
        ];
    }
}
