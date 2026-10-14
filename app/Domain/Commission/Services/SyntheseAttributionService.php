<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Demande\Models\Demande;
use App\Domain\Referentiel\Models\Service;
use App\Domain\Stock\Models\Appui;
use Illuminate\Support\Collection;

/**
 * Ce que la commission a sous les yeux au moment d'arbitrer : les dossiers mûrs
 * et, pour chaque appui, la confrontation entre ce qui est demandé et ce qui
 * reste réellement disponible.
 *
 * La logique vit ici plutôt que dans les contrôleurs parce que trois écrans
 * l'utilisent (la liste des demandes, l'écran d'attribution et la synthèse
 * imprimable) et qu'ils doivent tous compter de la même façon.
 */
class SyntheseAttributionService
{
    /**
     * Le tableau de confrontation, appui par appui, pour un ensemble de
     * dossiers. Trié par tension décroissante : ce qui manque le plus remonte
     * en tête, puisque c'est là que l'arbitrage se joue.
     *
     * @param  Collection<int, Demande>  $dossiers
     * @return Collection<int, array{libelle: string, unite: ?string, service: ?string, demandee: int, stock: int, engagee: int, disponible: int, couverte: bool, nb_dossiers: int}>
     */


    public function confrontationDemande(Collection $dossiers): Collection
    {
        /** @var Collection $lignes */
        $lignes = $dossiers->flatMap->lignes;

        if ($lignes->isEmpty()) {
            return collect();
        }

        // 1. Extraire les types d'appui concernés et les quantités demandées par type
    $idsTypesAppuis = $lignes->pluck('id_type_appui')->filter()->unique();
    $quantitesDemandees = $lignes->groupBy('id_type_appui')
        ->map(fn ($group) => $group->sum('quantite_demandee'));

        // 2. Récupérer TOUS les appuis en base pour ces types d'appui
    $appuis = Appui::with(['uniteDosage', 'typeAppui.service'])
        ->whereIn('id_type_appui', $idsTypesAppuis)
        ->get();

        // 3. Charger les quantités déjà engagées pour ces types d'appui
    $engagees = $this->quantitesEngagees($idsTypesAppuis);

// 4. Construire la synthèse appui par appui
    return $appuis
        ->map(function (Appui $appui) use ($lignes, $quantitesDemandees, $engagees) {
            $idType = $appui->id_type_appui;
            $idAppui = $appui->id_appui;

            $demandee = $quantitesDemandees->get($idType, 0);
            $stock = (int) ($appui->quantite_en_stock ?? 0);
            $engagee = (int) ($engagees[$idAppui] ?? 0);
            $disponible = $stock - $engagee;

            // Compte du nombre de dossiers demandant ce type d'appui
            $nbDossiers = $lignes->where('id_type_appui', $idType)->pluck('id_demande')->unique()->count();

            return [
                'id_appui' => $idAppui,
                'libelle' => $appui->libelle ?? 'Appui inconnu',
                'unite' => $appui->uniteDosage?->lib_unite,
                'service' => $appui->typeAppui?->service?->lib_service,
                'demandee' => $demandee,
                'stock' => $stock,
                'engagee' => $engagee,
                'disponible' => $disponible,
                'couverte' => $disponible >= $demandee,
                'nb_dossiers' => $nbDossiers,
            ];
        })
        ->sortBy([
            fn ($ligne) => $ligne['couverte'] ? 1 : 0,
            fn ($ligne) => -$ligne['demandee'],
        ])
        ->values();

    }

    /** La confrontation restreinte à un seul dossier. */
    public function confrontationPourDemande(Demande $demande): Collection
    {
        $demande->loadMissing('lignes.typeAppui', 'lignes.typeAppui.service');

        return $this->confrontationDemande(collect([$demande]));
    }

    /**
     * Ce que les attributions déjà prononcées ont réservé sur le stock. Seuls
     * les bons non encore sortis du magasin comptent : une fois servis, leur
     * quantité a déjà quitté `quantite_en_stock` et la déduire à nouveau la
     * compterait deux fois.
     *
     * @param  Collection<int, int>  $idsTypesAppuis
     * @return Collection<int, int>
     */
    public function quantitesEngagees(Collection $idsTypesAppuis): Collection
    {
        return LigneAttribution::query()
            ->whereHas('appui.typeAppui', function ($q)  use ($idsTypesAppuis) {
                return $q->whereIn('id_type_appui',$idsTypesAppuis);
            })
            ->whereHas('attribution', fn ($q) => $q->whereNull('date_destockage'))
            ->selectRaw('id_appui, SUM(quantite_attribuee) AS total')
            ->groupBy('id_appui')
            ->pluck('total', 'id_appui');
    }

    /**
     * Ce que l'écran de modification d'une attribution affiche : le catalogue
     * des appuis (ceux du service de l'utilisateur, s'il en a un) et, pour chacun,
     * le stock, l'engagé et ce que la demande réclamait pour son type d'appui.
     *
     * @return array{catalogue: Collection<int, Appui>, synthese: Collection<int, array{libelle: string, stock: int|float, engagee: int, demandee: int|float}>}
     */
    public function pourEdition(Attribution $attribution, ?Service $service): array
    {
        $demande = $attribution->demande;

        $catalogue = Appui::with(['uniteDosage', 'typesSystemesProductions'])->whereHas('typeAppui', function ($q) use ($service) {
            if ($service) {
                $q->where('id_service', $service->id_service);
            }
        })->get();

        $synthese = $catalogue->map(function (Appui $appui) use ($demande) {
            $ligneDemandee = $demande->lignes->first(
                fn ($l) => $l->typeAppui?->id_type_appui === $appui->id_type_appui
            );

            return [
                'libelle' => $appui->libelle,
                'stock' => $appui->quantite_en_stock ?? 0,
                'engagee' => $appui->quantiteEngagee(),
                'demandee' => $ligneDemandee?->quantite_demandee ?? 0,
            ];
        });

        return ['catalogue' => $catalogue, 'synthese' => $synthese];
    }
}
