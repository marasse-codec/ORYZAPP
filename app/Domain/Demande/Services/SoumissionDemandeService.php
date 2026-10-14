<?php

namespace App\Domain\Demande\Services;

use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\Traitement;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use Illuminate\Support\Facades\DB;

/**
 * La soumission d'une demande : elle porte la date, rattache la demande aux
 * services dont elle sollicite les appuis, et laisse sa trace au journal, le
 * tout ensemble ou pas du tout.
 *
 * Une demande ne se soumet pas sans visite de terrain enregistrée : la règle
 * est tenue ici, pour que nul chemin (écran, saisie en série) ne la contourne.
 * Les autres gardes (demande modifiable, au moins une ligne) restent à
 * l'appelant, qui sait comment répondre à l'utilisateur.
 */
class SoumissionDemandeService
{
    public function soumettre(Demande $demande): void
    {
        if (! $demande->terrainAEteVisite()) {
            throw new RegleMetierViolee("Une visite de terrain doit être enregistrée avant de soumettre la demande n°{$demande->id_demande}.");
        }

        DB::transaction(function () use ($demande) {
            $demande->soumettre();

            Traitement::creerTraitements($demande->id_demande, $demande->getServices());

            JournalAction::enregistrer(ActionJournalEnum::demandeSoumise, $demande);
        });
    }
}
