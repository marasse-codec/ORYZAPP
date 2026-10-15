<?php

namespace App\Domain\Journal\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Models\JournalAction;
use Illuminate\Support\Collection;

/**
 * La chronologie d'une demande : tout ce qui lui est arrivé, dans l'ordre
 * (création, modifications, visite, soumission, attribution, publication,
 * retrait, règlements, rendements…), reconstituée à partir du journal.
 *
 * Le statut d'une demande n'est pas stocké (il se déduit) ; c'est donc le
 * journal qui garde l'histoire de ses changements d'état. Les attributions
 * annulées (suppression douce) y figurent aussi.
 */
class ChronologieDemandeService
{
    /** @return Collection<int, JournalAction> de la plus ancienne à la plus récente */
    public function pour(Demande $demande): Collection
    {
        $attributions = Attribution::withTrashed()
            ->where('id_demande', $demande->id_demande)
            ->pluck('id_attribution');

        return JournalAction::query()
            ->with('user')
            ->where(function ($q) use ($demande, $attributions) {
                $q->where(fn ($d) => $d->where('cible_type', $demande->getMorphClass())->where('cible_id', $demande->id_demande))
                    ->orWhere(fn ($a) => $a->where('cible_type', (new Attribution)->getMorphClass())->whereIn('cible_id', $attributions));
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
