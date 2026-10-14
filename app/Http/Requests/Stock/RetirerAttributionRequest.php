<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

/** Le retrait d'un bon : date, récipiendaire, pièce d'identité scannée, équipements choisis pour le matériel. */
class RetirerAttributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_destockage' => 'required|date|before_or_equal:today',
            'recipiendaire' => 'required|string|max:255',
            'numero_piece_identite' => 'nullable|string|max:60',
            'piece_identite' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'equipements' => 'nullable|array',
            'equipements.*' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'piece_identite.required' => "Scannez la pièce d'identité du récipiendaire.",
            'recipiendaire.required' => 'Indiquez qui se présente pour le retrait.',
        ];
    }
}
