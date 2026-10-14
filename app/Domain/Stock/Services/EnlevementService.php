<?php

namespace App\Domain\Stock\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Journal\Enums\ActionJournalEnum;
use App\Domain\Journal\Models\JournalAction;
use App\Domain\Notification\Services\AlerteService;
use App\Domain\Shared\Exceptions\RegleMetierViolee;
use App\Domain\Stock\Enums\TypeMouvementStockEnum;
use App\Domain\Stock\Models\Appui;
use App\Domain\Stock\Models\Equipement;
use App\Domain\Stock\Models\MouvementStock;
use App\Domain\Stock\Models\TypeMouvementStock;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Le retrait au magasin : on sert un bon d'attribution publié. C'est le seul
 * endroit où les quantités en stock diminuent à la suite d'une attribution :
 * les mouvements, la baisse du stock, la mise en service du matériel, la date de
 * retrait, le récipiendaire et la trace au journal partent ensemble ou pas du tout.
 *
 * L'attribution est relue sous verrou : deux validations simultanées du même
 * bon ne déstockent pas deux fois. La pièce d'identité téléversée est effacée
 * si le retrait échoue. Les alertes de seuil ne partent qu'une fois la sortie
 * validée.
 */
class EnlevementService
{
    public function __construct(private AlerteService $alertes) {}

    /**
     * @param  array{date_destockage: string, recipiendaire: string, numero_piece_identite?: ?string}  $donnees
     * @param  array<int|string, array<int, int|string>>  $equipementsParLigne  id de ligne d'attribution => équipements choisis
     */
    public function servir(Attribution $attribution, array $donnees, UploadedFile $piece, User $operateur, array $equipementsParLigne = []): void
    {
        $chemin = $piece->store("retraits/{$attribution->id_attribution}", 'public');
        $franchissements = [];

        try {
            DB::transaction(function () use ($attribution, $donnees, $chemin, $operateur, $equipementsParLigne, &$franchissements) {
                $attribution = Attribution::whereKey($attribution->id_attribution)->lockForUpdate()->firstOrFail();

                if (! $attribution->estDestockable()) {
                    throw new RegleMetierViolee('Cette attribution a déjà été retirée, ou ses résultats ne sont pas encore publiés.');
                }

                $attribution->load('lignesAttributions.appui.typeAppui');
                $idSortie = TypeMouvementStock::idDe(TypeMouvementStockEnum::SORTIE);
                $idAgent = $operateur->idAgent();

                foreach ($attribution->lignesAttributions as $ligne) {
                    $franchissement = $this->servirLigne($attribution, $ligne, $equipementsParLigne[$ligne->id_ligne_attribution] ?? [], $idSortie, $idAgent, $donnees['date_destockage']);

                    if ($franchissement) {
                        $franchissements[] = $franchissement;
                    }
                }

                $attribution->update([
                    'date_destockage' => $donnees['date_destockage'],
                    'recipiendaire' => $donnees['recipiendaire'],
                    'numero_piece_identite' => $donnees['numero_piece_identite'] ?? null,
                    'chemin_piece_identite' => $chemin,
                ]);

                JournalAction::enregistrer(ActionJournalEnum::attributionRetiree, $attribution, [
                    'recipiendaire' => $donnees['recipiendaire'],
                    'date_destockage' => $donnees['date_destockage'],
                ]);
            });
        } catch (Throwable $e) {
            // Rien n'a été retiré : la pièce d'identité téléversée n'a plus lieu d'être.
            Storage::disk('public')->delete($chemin);

            throw $e;
        }

        foreach ($franchissements as ['appui' => $appui, 'stock' => $stock]) {
            $this->alertes->stockSousSeuil($appui, $stock);
        }
    }

    /** Le matériel se sert équipement par équipement ; le reste (intrants) par quantité. */
    private function servirLigne(Attribution $attribution, LigneAttribution $ligne, array $equipementIds, int $idSortie, int $idAgent, string $date): ?array
    {
        $appui = $ligne->appui;
        $equipementIds = array_values(array_unique($equipementIds));

        if ($appui->typeAppui?->est_intrant === false) {
            if (count($equipementIds) < $ligne->quantite_attribuee ) {
                throw new RegleMetierViolee("Veuillez sélectionner exactement {$ligne->quantite_attribuee} équipement(s) pour {$appui->libelle}.");
            }

            return $this->sortirEquipements($attribution, $ligne, $equipementIds, $idSortie, $idAgent, $date);
        }

        return $this->sortirIntrant($attribution, $ligne, $idSortie, $idAgent, $date);
    }

    /**
     * Sortie d'un consommable : le stock baisse d'un seul coup, et seulement s'il
     * suffit. La ligne de l'appui est verrouillée le temps de la transaction : un
     * autre retrait simultané attend, puis voit le stock déjà diminué. Le
     * décrément passe par le modèle pour que ses observers (règles d'alerte de
     * stock) se déclenchent comme pour toute autre modification.
     *
     * @return array{appui: Appui, stock: int}|null l'appui, s'il passe sous son seuil d'alerte
     */
    private function sortirIntrant(Attribution $attribution, LigneAttribution $ligne, int $idSortie, int $idAgent, string $date): ?array
    {
        $appui = Appui::whereKey($ligne->id_appui)->lockForUpdate()->firstOrFail();

        if ($appui->quantite_en_stock < $ligne->quantite_attribuee) {
            throw new RegleMetierViolee("Stock insuffisant pour {$appui->libelle} : {$ligne->quantite_attribuee} demandé(s), {$appui->quantite_en_stock} disponible(s).");
        }

        $avant = $appui->quantite_en_stock;
        $appui->decrement('quantite_en_stock', $ligne->quantite_attribuee);

        MouvementStock::create([
            'id_appui' => $appui->id_appui,
            'id_type_mouvement_stock' => $idSortie,
            'id_agent' => $idAgent,
            'id_attribution' => $attribution->id_attribution,
            'quantite' => $ligne->quantite_attribuee,
            'date_mouvement' => $date,
        ]);

        $apres = $appui->quantite_en_stock;

        return AlerteService::franchitLeSeuil($appui, $avant, $apres) ? ['appui' => $appui, 'stock' => $apres] : null;
    }

    /**
     * Sortie de matériel : un mouvement par équipement physique. Le passage « en
     * service » se fait par une mise à jour conditionnelle : un équipement déjà
     * sorti, ou d'un autre appui, fait échouer toute la sortie.
     *
     * @param  array<int, int|string>  $equipementIds
     * @return array{appui: Appui, stock: int}|null l'appui, s'il passe sous son seuil d'alerte
     */
    private function sortirEquipements(Attribution $attribution, LigneAttribution $ligne, array $equipementIds, int $idSortie, int $idAgent, string $date): ?array
    {
        $appui = $ligne->appui;

        $sortis = Equipement::whereIn('id_equipement', $equipementIds)
            ->where('id_appui', $appui->id_appui)
            ->where('en_service', false)
            ->update(['en_service' => true]);

        if ($sortis !== count($equipementIds)) {
            throw new RegleMetierViolee("Un des équipements choisis pour {$appui->libelle} n'est pas disponible.");
        }

        $appui->decrement('quantite_en_stock', $ligne->quantite_attribuee);

        foreach ($equipementIds as $idEquipement) {
            MouvementStock::create([
                'id_appui' => $appui->id_appui,
                'id_equipement' => $idEquipement,
                'id_type_mouvement_stock' => $idSortie,
                'id_agent' => $idAgent,
                'id_attribution' => $attribution->id_attribution,
                'quantite' => 1,
                'date_mouvement' => $date,
            ]);
        }

        // Pour le matériel, le stock qui compte est le nombre d'équipements disponibles.
        $apres = $appui->equipements()->where('en_service', false)->count();

        return AlerteService::franchitLeSeuil($appui, $apres + $sortis, $apres) ? ['appui' => $appui, 'stock' => $apres] : null;
    }
}
