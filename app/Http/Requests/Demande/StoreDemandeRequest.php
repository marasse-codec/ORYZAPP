<?php

namespace App\Http\Requests\Demande;

use App\Http\Requests\Concerns\ReglesNumeriques;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandeRequest extends FormRequest
{
    /** La direction et la comptabilité consultent sans saisir de demande (cf. RoleEnum::peutSaisirDesDemandes). */
    public function authorize(): bool
    {
        return $this->user()?->roleEnum()?->peutSaisirDesDemandes() ?? true;
    }

    public function rules(): array
    {
        return [
            // Étape 1 : Profil
            'id_type_acteur'              => ['required', 'exists:types_acteurs,id_type_acteur'],
            
            // Étape 2 : Demandeur
            'nom_structure'               => ['nullable', 'string', 'max:150'],
            'nom'                         => ['required', 'string', 'max:255'],
            'telephone'                   => ['required', 'string', 'max:20'],
            'email'                       => ['nullable', 'email', 'max:255'],

            // Étape 3 : Demande & Localités (Tableau d'IDs)
            'localites'                   => ['required', 'array', 'min:1'],
            'localites.*'                 => ['required', 'integer', 'exists:localites,id_localite'],
            'id_campagne'                 => ['required', 'exists:campagnes,id_campagne'],

            'periode_debut_mise_en_place' => ['required', 'date'],
            'periode_fin_mise_en_place'   => ['required', 'date', 'after:periode_debut_mise_en_place'],

            // Superficies par système de production
            'superficies'                 => ['nullable', 'array'],
            'superficies.*'               => ['nullable', ...ReglesNumeriques::superficie()],

            // Étape 4 : Appuis sollicités
            'lignes'                      => ['required', 'array', 'min:1'],
            'lignes.*.id_type_appui'      => ['required', 'exists:types_appuis,id_type_appui'],
            'lignes.*.id_appui'           => ['nullable','exists:appuis,id_appui'],
            'lignes.*.quantite_demandee'  => ['nullable', ...ReglesNumeriques::quantite(), ReglesNumeriques::entierPourEquipement()],

            // Pièces justificatives
            'pieces'                      => ['nullable', 'array'],
            'pieces.*'                    => ['nullable', 'file', 'max:10240'],

            'date_naissance'              => ['required']
        ];
    }

    /**
     * SNF04 : chaque message désigne explicitement le champ en cause.
     */
    public function messages(): array
    {
        return [
            'id_type_acteur.required'              => "Le type d'acteur est obligatoire.",
            'id_type_acteur.exists'                => "Le type d'acteur sélectionné est invalide.",

            'nom_structure.max'                    => 'Le nom de la structure ne peut pas dépasser 150 caractères.',
            'nom.required'                         => 'Le nom du représentant est obligatoire.',
            'telephone.required'                   => 'Le téléphone est obligatoire.',
            'email.email'                          => "L'adresse e-mail est invalide.",

            'localites.required'                   => 'Au moins une localité doit être sélectionnée.',
            'localites.min'                        => 'Veuillez sélectionner au moins une localité.',
            'localites.*.exists'                   => 'Une des localités sélectionnées est invalide.',

            'id_campagne.required'                 => 'La campagne est obligatoire.',
            'id_campagne.exists'                   => 'La campagne sélectionnée est invalide.',

            'periode_debut_mise_en_place.required' => 'La date de début de mise en place est obligatoire.',
            'periode_debut_mise_en_place.date'     => 'La date de début de mise en place est invalide.',
            'periode_fin_mise_en_place.required'   => 'La date de fin de mise en place est obligatoire.',
            'periode_fin_mise_en_place.date'       => 'La date de fin de mise en place est invalide.',
            'periode_fin_mise_en_place.after' => 'La date de fin doit être postérieure à la date de début.',

            'lignes.required'                      => 'Au moins un type d\'appui doit être sollicité.',
            'lignes.min'                           => 'Au moins un type d\'appui doit être sollicité.',
            'lignes.*.id_type_appui.required'      => "Le type d'appui est obligatoire pour chaque ligne.",
            'lignes.*.id_type_appui.exists'        => "Le type d'appui sélectionné est invalide.",
        ];
    }
}