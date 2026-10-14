<?php

namespace App\Domain\Stock\Services;

use App\Utils\Helper;

use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Approvisionnement;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Une entrée de stock : le bon d'approvisionnement, ses mouvements, les
 * équipements créés pour le matériel suivi à l'unité, la hausse du stock et la
 * trace au journal, ensemble ou pas du tout. Le contrôle des données saisies
 * (formulaire) se fait en amont, dans la requête HTTP.
 */
class ApprovisionnementService
{
    /**
     * @param  array{date_approvisionnement: string, reference_commande?: ?string, origine?: ?string, lignes: array<int, array{id_appui: int|string, quantite_ajoutee: float|int|string, matricules?: array<int, ?string>}>}  $donnees
     */
    public function enregistrer(array $donnees, User $operateur): Approvisionnement
    {
        $idAgent = $operateur->idAgent();
        $idEntree = TypeMouvementStock::idDe(TypeMouvementStockEnum::ENTREE);

        return DB::transaction(function () use ($donnees, $idAgent, $idEntree) {
            $approvisionnement = Approvisionnement::create([
                'date_approvisionnement' => $donnees['date_approvisionnement'],
                'reference_commande' => $donnees['reference_commande'] ?? null,
                'origine' => $donnees['origine'] ?? null,
            ]);

            foreach ($donnees['lignes'] as $ligne) {
                $appui = Appui::findOrFail($ligne['id_appui']);
                $quantite = Helper::arrondiQuantite($ligne['quantite_ajoutee']);

                $matricules = array_filter($ligne['matricules'] ?? []);

                if ($matricules) {
                    $this->entrerEquipements($approvisionnement, $appui, $matricules, $idAgent, $idEntree, $donnees['date_approvisionnement']);
                } else {
                    $this->entrerConsommable($approvisionnement, $appui, $quantite, $idAgent, $idEntree, $donnees['date_approvisionnement']);
                }

                // Incrémentation globale du stock de l'appui
                $appui->increment('quantite_en_stock', $quantite);
            }

            JournalAction::enregistrer(ActionJournalEnum::approvisionnementEnregistre, $approvisionnement, [
                'reference_commande' => $donnees['reference_commande'] ?? null,
                'lignes' => count($donnees['lignes']),
            ]);

            return $approvisionnement;
        });
    }

    /** Matériel suivi à l'unité : un équipement et un mouvement par matricule. */
    private function entrerEquipements(Approvisionnement $approvisionnement, Appui $appui, array $matricules, int $idAgent, int $idEntree, string $date): void
    {
        foreach ($matricules as $matricule) {
            $equipement = Equipement::create([
                'id_appui' => $appui->id_appui,
                'matricule_equipement' => $matricule,
                'etat' => 'bon',
                'en_service' => false,
            ]);

            MouvementStock::create([
                'id_approvisionnement' => $approvisionnement->id_approvisionnement,
                'id_appui' => $appui->id_appui,
                'id_equipement' => $equipement->id_equipement,
                'quantite' => 1,
                'id_agent' => $idAgent,
                'id_type_mouvement_stock' => $idEntree,
                'date_mouvement' => $date,
            ]);
        }
    }

    /** Consommable : un mouvement global. */
    private function entrerConsommable(Approvisionnement $approvisionnement, Appui $appui, float $quantite, int $idAgent, int $idEntree, string $date): void
    {
        MouvementStock::create([
            'id_approvisionnement' => $approvisionnement->id_approvisionnement,
            'id_appui' => $appui->id_appui,
            'id_equipement' => null,
            'quantite' => $quantite,
            'id_agent' => $idAgent,
            'id_type_mouvement_stock' => $idEntree,
            'date_mouvement' => $date,
        ]);
    }
}
