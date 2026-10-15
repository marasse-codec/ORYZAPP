<?php

namespace App\Domain\Referentiel\Services;

use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Clôturer une campagne, c'est la figer : plus de nouvelle demande, plus de
 * modification de demande, d'attribution ni de rendement. Ses chiffres
 * deviennent définitifs et se comparent sans risque aux autres campagnes.
 *
 * Les règlements restent possibles : une somme due ne disparaît pas parce que
 * la campagne est terminée. Une clôture faite par erreur se rouvre (tracé au journal).
 */
class ClotureCampagneService
{
    /**
     * Ce qu'il reste en cours dans la campagne : à montrer avant de confirmer la clôture.
     *
     * @return array{brouillons: int, a_arbitrer: int}
     */
    public function enCours(Campagne $campagne): array
    {
        $demandes = Demande::query()->where('id_campagne', $campagne->id_campagne);

        return [
            'brouillons' => (clone $demandes)->whereNull('date_soummission')->count(),
            'a_arbitrer' => (clone $demandes)->aArbitrer()->count(),
        ];
    }

    public function cloturer(Campagne $campagne): void
    {
        DB::transaction(function () use ($campagne) {
            $campagne = Campagne::whereKey($campagne->id_campagne)->lockForUpdate()->firstOrFail();

            if ($campagne->estCloturee()) {
                throw new RegleMetierViolee("La campagne « {$campagne->lib_campagne} » est déjà clôturée.");
            }

            $campagne->update(['cloturee_le' => now(), 'id_user_cloture' => Auth::id()]);

            JournalAction::enregistrer(ActionJournalEnum::campagneCloturee, $campagne, $this->enCours($campagne));
        });
    }

    public function rouvrir(Campagne $campagne, string $motif): void
    {
        DB::transaction(function () use ($campagne, $motif) {
            $campagne = Campagne::whereKey($campagne->id_campagne)->lockForUpdate()->firstOrFail();

            if (! $campagne->estCloturee()) {
                throw new RegleMetierViolee("La campagne « {$campagne->lib_campagne} » n'est pas clôturée.");
            }

            $campagne->update(['cloturee_le' => null, 'id_user_cloture' => null]);

            JournalAction::enregistrer(ActionJournalEnum::campagneRouverte, $campagne, ['motif' => $motif]);
        });
    }
}
