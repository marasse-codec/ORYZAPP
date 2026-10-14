<?php

namespace App\Http\Requests\Commission;

use App\Http\Requests\Concerns\ReglesNumeriques;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Demande\Models\Demande;
use Illuminate\Foundation\Http\FormRequest;

/** Les nouvelles lignes d'une attribution modifiée et son commentaire. */
class ModifierAttributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commentaires' => 'nullable|string|max:2000',
            'superficies' => 'nullable|array',
            'superficies.*' => ['nullable', ...ReglesNumeriques::superficie()],
            'lignes' => 'required|array|min:1',
            'lignes.*.id_appui' => 'required|distinct|exists:appuis,id_appui',
            'lignes.*.quantite_attribuee' => ['required', ...ReglesNumeriques::quantite(strictementPositive: true), ReglesNumeriques::entierPourEquipement()],
            'lignes.*.cout_attribution' => ['nullable', ...ReglesNumeriques::prix()],
            'lignes.*.remise' => ['nullable', ...ReglesNumeriques::remise()],
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
        $attribution = $this->route('attribution');
        $id = $attribution instanceof Attribution ? $attribution->getKey() : $attribution;

        return Attribution::with('demande.systemesProductions')->find($id)?->demande;
    }

    public function messages(): array
    {
        return [
            'lignes.required' => 'Attribuez au moins un appui.',
            'lignes.*.id_appui.distinct' => 'Un même appui ne peut être attribué deux fois.',
        ];
    }
}
