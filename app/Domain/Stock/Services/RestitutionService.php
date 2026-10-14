<?php

namespace App\Domain\Stock\Services;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * La restitution de matériel confié : l'équipement revient au magasin dans l'état
 * constaté, n'est plus en service et redevient attribuable ; le stock de l'appui
 * remonte d'une unité, et un mouvement « retour » en garde la trace, signé par
 * l'agent qui l'enregistre, rattaché au bon de la sortie d'origine. Un lot de restitutions est tout ou rien ; seul un
 * équipement actuellement en service peut être restitué.
 */
class RestitutionService
{
    /**
     * @param  array<int, array{id_equipement: int|string, etat: string}>  $retours
     * @return int nombre d'équipements restitués
     */
    public function restituer(array $retours, User $operateur): int
    {
        $idAgent = $operateur->idAgent();
        $idRetour = TypeMouvementStock::idDe(TypeMouvementStockEnum::RETOUR);
        $idSortie = TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE);

        return DB::transaction(function () use ($retours, $idAgent, $idRetour, $idSortie) {
            foreach ($retours as $retour) {
                $equipement = Equipement::whereKey($retour['id_equipement'])->lockForUpdate()->firstOrFail();

                if (! $equipement->en_service) {
                    throw new RegleMetierViolee("L'équipement {$equipement->matricule_equipement} n'est pas en service : il ne peut pas être restitué.");
                }

                // Un mouvement vient toujours d'un bon : le retour se rattache à l'attribution qui a fait sortir l'équipement.
                $idAttribution = MouvementStock::where('id_equipement', $equipement->id_equipement)
                    ->where('id_type_mouvement_stock', $idSortie)
                    ->orderByDesc('date_mouvement')->orderByDesc('id_mouvement_stock')
                    ->value('id_attribution');

                if ($idAttribution === null) {
                    throw new RegleMetierViolee("L'équipement {$equipement->matricule_equipement} est en service sans sortie enregistrée : restitution impossible.");
                }

                $equipement->update(['etat' => $retour['etat'], 'en_service' => false]);

                // La sortie avait retiré une unité du stock : la restitution la lui rend.
                Appui::whereKey($equipement->id_appui)->lockForUpdate()->firstOrFail()->increment('quantite_en_stock');

                MouvementStock::create([
                    'id_appui' => $equipement->id_appui,
                    'id_equipement' => $equipement->id_equipement,
                    'id_type_mouvement_stock' => $idRetour,
                    'id_agent' => $idAgent,
                    'id_attribution' => $idAttribution,
                    'quantite' => 1,
                    'date_mouvement' => now(),
                ]);

                JournalAction::enregistrer(ActionJournalEnum::equipementRestitue, $equipement, ['etat' => $retour['etat']]);
            }

            return count($retours);
        });
    }
}
