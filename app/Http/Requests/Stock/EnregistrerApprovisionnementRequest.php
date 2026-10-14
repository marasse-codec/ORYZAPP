<?php

namespace App\Http\Requests\Stock;

use App\Http\Requests\Concerns\ReglesNumeriques;

use Illuminate\Foundation\Http\FormRequest;

/** Une entrée de stock : consommables par quantité, matériel par matricules (uniques, y compris en base). */
class EnregistrerApprovisionnementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_approvisionnement' => 'required|date|before_or_equal:today',
            'reference_commande' => 'nullable|string|max:100',
            'origine' => 'nullable|string|max:255',

            'lignes' => 'required|array|min:1',
            'lignes.*.id_appui' => 'required|exists:appuis,id_appui',
            'lignes.*.quantite_ajoutee' => ['required', ...ReglesNumeriques::quantite(strictementPositive: true), ReglesNumeriques::entierPourEquipement()],
            'lignes.*.matricules' => 'nullable|array',
            'lignes.*.matricules.*' => 'nullable|string|distinct|max:100|unique:equipements,matricule_equipement',
        ];
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Vous devez ajouter au moins un appui.',
            'lignes.*.id_appui.required' => 'Veuillez sélectionner un appui valide.',
            'lignes.*.matricules.*.unique' => 'Un de ces matricules existe déjà en base.',
        ];
    }
}
