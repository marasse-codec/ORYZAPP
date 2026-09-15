<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajustez si l'accès dépend du rôle (ex: seul un délégué peut saisir).
        return true;
    }

    public function rules(): array
    {
        return [
            'id_type_acteur' => ['required', 'exists:types_acteurs,id_type_acteur'],
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_localite' => ['required', 'exists:localites,id_localite'],
            'id_campagne' => ['required', 'exists:campagnes,id_campagne'],
            'debut_periode_mise_en_place' => ['required', 'date'],

            'superficies' => ['nullable', 'array'],
            'superficies.*' => ['nullable', 'numeric', 'min:0'],

            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.id_appui' => ['required', 'exists:appuis,id_appui'],
            'lignes.*.quantite_demandee' => ['required', 'numeric', 'min:0.01'],

            'pieces' => ['nullable', 'array'],
            'pieces.*' => ['nullable', 'file', 'max:10240'],
        ];
    }

    /**
     * SNF04 : chaque message désigne explicitement le champ en cause.
     */
    public function messages(): array
    {
        return [
            'id_type_acteur.required' => "Le type d'acteur est obligatoire.",
            'id_type_acteur.exists' => "Le type d'acteur sélectionné est invalide.",
            'nom.required' => 'Le nom est obligatoire.',
            'id_campagne' => 'La campagne est obligatoire',
            'telephone.required' => 'Le téléphone est obligatoire.',
            'id_localite.required' => 'La localité est obligatoire.',
            'id_localite.exists' => 'La localité sélectionnée est invalide.',
            'debut_periode_mise_en_place.required' => 'La date de début de la période de mise en place est obligatoire.',
            'debut_periode_mise_en_place.date' => 'La date de début de la période de mise en place est invalide.',
            'lignes.required' => 'Au moins une ligne de demande est requise.',
            'lignes.*.id_appui.required' => "L'appui est obligatoire pour chaque ligne.",
            'lignes.*.id_appui.exists' => "L'appui sélectionné pour une ligne est invalide.",
            'lignes.*.quantite_demandee.required' => 'La quantité demandée est obligatoire pour chaque ligne.',
            'lignes.*.quantite_demandee.min' => 'La quantité demandée doit être supérieure à zéro.',
        ];
    }
}