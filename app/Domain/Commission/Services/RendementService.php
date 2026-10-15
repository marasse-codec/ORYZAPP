<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Demande\Models\Rendement;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Le retour du DP sur les superficies d'une demande : ce que chacune a produit.
 *
 * Il se transmet dès la publication de l'attribution et n'ajoute rien au parcours
 * de la demande : aucun statut, aucune condition de clôture. Le DP peut revenir
 * corriger ce qu'il a envoyé.
 */
class RendementService
{
    /**
     * Enregistre (ou corrige) les rendements des superficies de la demande ; les lignes laissées
     * vides sont ignorées, de sorte qu'on peut renseigner les systèmes de production un à un.
     *
     * @param  array<int|string, array{superficie_recoltee?: mixed, production_tonnes?: mixed}>  $lignes  par id de système de production
     */
    public function enregistrer(Attribution $attribution, array $lignes): int
    {
        return DB::transaction(function () use ($attribution, $lignes) {
            $attribution = Attribution::query()->lockForUpdate()->findOrFail($attribution->id_attribution);

            if (! $attribution->estPubliee()) {
                throw new RegleMetierViolee("Les résultats de l'attribution n°{$attribution->id_attribution} ne sont pas publiés : les rendements ne peuvent pas encore être transmis.");
            }

            $systemes = $attribution->demande->systemesProductions()->get()->keyBy('id_systeme_production');
            $enregistres = 0;
            $production = 0.0;

            foreach ($lignes as $idSysteme => $ligne) {
                $recoltee = $ligne['superficie_recoltee'] ?? null;
                $tonnes = $ligne['production_tonnes'] ?? null;

                if ($recoltee === null || $recoltee === '' || $tonnes === null || $tonnes === '') {
                    continue;
                }

                $systeme = $systemes->get((int) $idSysteme)
                    ?? throw new RegleMetierViolee("La superficie n°{$idSysteme} n'appartient pas à la demande de cette attribution.");

                if ((float) $recoltee > (float) $systeme->superficie) {
                    throw new RegleMetierViolee(sprintf(
                        'La superficie récoltée (%s ha) dépasse la superficie déclarée (%s ha).',
                        (float) $recoltee,
                        (float) $systeme->superficie,
                    ));
                }

                Rendement::updateOrCreate(
                    ['id_systeme_production' => $systeme->id_systeme_production],
                    ['superficie_recoltee' => $recoltee, 'production_tonnes' => $tonnes, 'id_user' => Auth::id()],
                );

                $enregistres++;
                $production += (float) $tonnes;
            }

            if ($enregistres === 0) {
                throw new RegleMetierViolee('Renseignez au moins une superficie avant de transmettre.');
            }

            JournalAction::enregistrer(ActionJournalEnum::rendementEnvoye, $attribution, [
                'superficies' => $enregistres,
                'production_tonnes' => round($production, 3),
            ]);

            return $enregistres;
        });
    }
}
