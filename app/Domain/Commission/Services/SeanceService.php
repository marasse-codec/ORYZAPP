<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Notification\Services\AlerteService;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Support\Facades\DB;

/**
 * La vie d'une séance de commission : ouverture, clôture (qui valide les
 * attributions), publication des résultats.
 *
 * La clôture et la publication relisent la séance sous verrou : les attributions
 * qui se saisissent en même temps (cf. AttributionService) attendent, puis
 * voient la séance dans son nouvel état. Les notifications ne partent qu'une
 * fois la publication validée.
 */
class SeanceService
{
    public function __construct(private AlerteService $alertes) {}

    public function ouvrir(?string $resume = null): SessionCommission
    {
        return DB::transaction(function () use ($resume) {
            $session = SessionCommission::create([
                'resume' => $resume,
                'date_debut_session' => today()->toDateString(),
            ]);

            JournalAction::enregistrer(ActionJournalEnum::sessionOuverte, $session);

            return $session;
        });
    }

    public function cloturer(SessionCommission $session): void
    {
        DB::transaction(function () use ($session) {
            $session = SessionCommission::whereKey($session->id_session)->lockForUpdate()->firstOrFail();

            if (! $session->estOuverte()) {
                throw new RegleMetierViolee('Cette séance est déjà clôturée.');
            }

            $session->update(['date_fin_session' => today()->toDateString()]);

            JournalAction::enregistrer(ActionJournalEnum::sessionCloturee, $session);
        });
    }

    /**
     * Publie les résultats d'une séance clôturée.
     *
     * @return int nombre d'attributions publiées (0 : il n'y avait rien à publier)
     */
    public function publier(SessionCommission $session): int
    {
        $publiees = DB::transaction(function () use ($session) {
            $session = SessionCommission::whereKey($session->id_session)->lockForUpdate()->firstOrFail();

            if (! $session->estFinie()) {
                throw new RegleMetierViolee('La séance doit être clôturée avant la publication des résultats.');
            }

            return $session->publierAttributions();
        });

        if ($publiees > 0) {
            $this->alertes->resultatsPublies($session);
        }

        return $publiees;
    }
}
