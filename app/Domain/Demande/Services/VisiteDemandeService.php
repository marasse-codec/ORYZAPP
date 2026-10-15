<?php

namespace App\Domain\Demande\Services;

use App\Domain\Demande\Models\Demande;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Referentiel\Models\Campagne;
use Illuminate\Support\Facades\DB;

/**
 * L'enregistrement de la visite de terrain d'une demande : ses observations, et
 * la trace au journal, ensemble ou pas du tout. Sans visite, une demande ne se
 * soumet pas (cf. SoumissionDemandeService).
 */
class VisiteDemandeService
{
    public function enregistrer(Demande $demande, string $observations): void
    {
        Campagne::exigerOuverteParId($demande->id_campagne, 'enregistrer une visite');

        DB::transaction(function () use ($demande, $observations) {
            $demande->marquerVisite($observations);

            JournalAction::enregistrer(ActionJournalEnum::demandeVisitee, $demande);
        });
    }
}
