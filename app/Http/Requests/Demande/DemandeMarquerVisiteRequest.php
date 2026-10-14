<?php

namespace App\Http\Requests\Demande;

use Illuminate\Foundation\Http\FormRequest;

class DemandeMarquerVisiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Étape 1 : Profil
            'id_demande' => ['required'],
            'observations'  => ['required']
        ];
    }

    /**
     * SNF04 : chaque message désigne explicitement le champ en cause.
     */
    public function messages(): array
    {
        return [
            'id_demande.required'       => "La demande est manquante",
            'observations.required'              => "L'observation est manquante",
        ];
    }
}