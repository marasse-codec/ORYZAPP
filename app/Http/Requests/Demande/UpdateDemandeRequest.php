<?php

namespace App\Http\Requests\Demande;

use App\Domain\Demande\Support\ReglesDemande;
use App\Http\Requests\Concerns\ReglesNumeriques;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDemandeRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    /** La direction et la comptabilité consultent sans saisir de demande (cf. RoleEnum::peutSaisirDesDemandes). */
    public function authorize(): bool
    {
        return $this->user()?->roleEnum()?->peutSaisirDesDemandes() ?? true;
    }

    /**
     * Règles de validation appliquées à la requête de mise à jour.
     */
    public function rules(): array
    {
        return [
            // --- ÉTAPE 1 : PROFIL ---
            'id_type_acteur' => ['required', 'exists:types_acteurs,id_type_acteur'],

            // --- ÉTAPE 2 : DEMANDEUR ---
            'id_acteur' => ['nullable', 'exists:acteurs,id_acteur'],
            'nom' => ['required', 'string', 'max:255'],
            'date_naissance' => ReglesDemande::dateNaissance(),   // majeur : 18 ans révolus
            'telephone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'nom_structure' => ['nullable', 'string', 'max:255'],

            // --- ÉTAPE 3 : INFOS DEMANDE & LOCALITÉS ---
            'id_campagne' => ['required', 'exists:campagnes,id_campagne'],
            'selected_departement' => ['nullable', 'exists:departements,id_departement'],
            'localites' => ['required', 'array', 'min:1'],
            'localites.*' => ['exists:localites,id_localite'],
            
            // Superficies ventilées par système de production
            'superficies' => ['nullable', 'array'],
            'superficies.*' => ['nullable', ...ReglesNumeriques::superficie()],

            // --- ÉTAPE 4 : APPUIS ---
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.id_type_appui' => ['required', 'exists:types_appuis,id_type_appui'],
            'lignes.*.id_appui' => ['nullable', 'exists:appuis,id_appui'],
            'lignes.*.quantite_demandee' => ['nullable', ...ReglesNumeriques::quantite(), ReglesNumeriques::entierPourEquipement()],

            // --- PIÈCES JOINTES ---
            // Les pièces jointes sont optionnelles en modification (nullable) 
            // car l'utilisateur peut choisir de conserver les fichiers existants
            'pieces' => ['nullable', 'array'],
            'pieces.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'], // max 4Mo par fichier
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'id_type_acteur.required' => 'Le type d\'acteur est obligatoire.',
            'nom.required' => 'Le nom du demandeur ou de la structure est obligatoire.',
            'date_naissance.required' => 'La date de naissance ou de création est obligatoire.',
            'date_naissance.before_or_equal' => ReglesDemande::messageMajorite(),
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'id_campagne.required' => 'La campagne est obligatoire.',
            'localites.required' => 'Vous devez sélectionner au moins une localité.',
            'localites.min' => 'Vous devez sélectionner au moins une localité.',
            'lignes.required' => 'Au moins un type d\'appui doit être demandé.',
            'lignes.min' => 'Au moins un type d\'appui doit être demandé.',
            'lignes.*.id_type_appui.required' => 'Chaque ligne doit comporter un type d\'appui.',
            'pieces.*.mimes' => 'Les pièces jointes doivent être au format PDF, JPG, JPEG ou PNG.',
            'pieces.*.max' => 'Chaque pièce jointe ne doit pas dépasser 4 Mo.',
        ];
    }

    /**
     * Préparation des données avant validation (nettoyage / typage).
     */
    protected function prepareForValidation(): void
    {
        // Nettoyage des tableaux d'inputs Alpine JS avant passage aux règles de validation
        if ($this->has('lignes') && is_array($this->lignes)) {
            $lignesFiltrees = array_filter($this->lignes, function ($ligne) {
                return !empty($ligne['id_type_appui']);
            });

            $this->merge([
                'lignes' => array_values($lignesFiltrees),
            ]);
        }
    }
}