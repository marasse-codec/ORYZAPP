<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class RestituerEquipementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'retours' => ['required', 'array', 'min:1'],
            'retours.*.id_equipement' => ['required', 'exists:equipements,id_equipement'],
            'retours.*.etat' => ['required', 'in:Bon état,Abîmé,Défectueux'],
        ];
    }
}
