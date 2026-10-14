<?php

namespace App\Http\Requests\Commission;

use App\Domain\Commission\Enums\ModePaiementEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** L'encaissement d'une partie (ou de la totalité) d'une attribution. */
class EnregistrerPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'min:0.01', 'decimal:0,2', 'max:9999999999.99'],
            'date_paiement' => ['required', 'date', 'before_or_equal:today'],
            'mode' => ['required', Rule::enum(ModePaiementEnum::class)],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.required' => 'Le montant réglé est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant doit être supérieur à zéro.',
            'date_paiement.required' => 'La date du règlement est obligatoire.',
            'date_paiement.before_or_equal' => 'La date du règlement ne peut pas être dans le futur.',
            'mode.required' => 'Le mode de règlement est obligatoire.',
            'mode.enum' => 'Le mode de règlement sélectionné est invalide.',
            'reference.max' => 'La référence ne peut pas dépasser 100 caractères.',
        ];
    }
}
