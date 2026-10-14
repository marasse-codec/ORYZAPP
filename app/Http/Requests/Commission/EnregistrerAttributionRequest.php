<?php

namespace App\Http\Requests\Commission;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Demande\Models\Demande;
use Illuminate\Foundation\Http\FormRequest;

/** Les lignes d'une nouvelle attribution : un appui, une quantité, un coût et une remise facultatifs. */
class EnregistrerAttributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaires' => 'nullable|string|max:500',
            'superficies' => 'nullable|array',
            'superficies.*' => 'nullable|numeric|min:0',
            'lignes' => 'required|array|min:1',
            'lignes.*.id_appui' => 'required|distinct|exists:appuis,id_appui',
            'lignes.*.quantite_attribuee' => 'required|numeric|min:0.0001',
            'lignes.*.cout_attribution' => 'nullable|numeric|min:0.0001',
            'lignes.*.remise' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /** Une écologie hors de la demande, ou une superficie au-delà de ce qui est demandé, est refusée. */
    public function after(): array
    {
        return [function ($validator) {
            $demande = $this->demandeConcernee();
            $demandees = $demande?->systemesProductions->pluck('superficie', 'id_type_systeme_production') ?? collect();

            foreach ((array) $this->input('superficies', []) as $idType => $superficie) {
                if (! $demandees->has($idType)) {
                    $validator->errors()->add("superficies.{$idType}", "Cette écologie ne fait pas partie de la demande.");
                } elseif ((float) $superficie > (float) $demandees[$idType]) {
                    $validator->errors()->add("superficies.{$idType}", "La superficie traitée ne peut pas dépasser la superficie demandée ({$demandees[$idType]} ha).");
                }
            }
        }];
    }

    private function demandeConcernee(): ?Demande
    {
        return Demande::with('systemesProductions')->find($this->route('idDemande'));
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Attribuez au moins un appui.',
            'lignes.*.id_appui.distinct' => 'Un même appui ne peut être attribué deux fois.',
        ];
    }
}
