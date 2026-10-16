<?php

namespace App\Domain\Commission\Services;

use App\Domain\Commission\Models\LigneAttribution;
use App\Domain\Demande\Models\Demande;
use Illuminate\Support\Collection;

/**
 * Ce que la commission a sous les yeux au moment d'arbitrer : les dossiers mûrs
 * et, pour chaque appui, la confrontation entre ce qui est demandé et ce qui
 * reste réellement disponible.
 *
 * Trois écrans s'en servent (la liste des demandes, l'écran de séance et la
 * synthèse imprimable) et un quatrième, l'attribution d'un dossier, compte de
 * la même façon : le calcul n'existe donc qu'une fois, dans SyntheseAttributionService.
 */
class SyntheseCommissionService
{
    public function __construct(private readonly SyntheseAttributionService $attributions) {}

    /**
     * Les dossiers en attente d'arbitrage : soumis, ni rejetés ni déjà
     * attribués. Ils peuvent viser le cycle en cours ou un cycle à venir
     * (préparation de la saison suivante) : la séance affiche le cycle de chacun.
     *
     * @return Collection<int, Demande>
     */
    public function dossiersEnAttente(): Collection
    {
        return Demande::query()
            ->with([
                'acteur',
                'campagne',
                'localites',
                'lignes',
                'SystemesProductions',
            ])
            ->orderBy('id_demande')
            ->aArbitrer()
            ->get()
            ->values();
    }

    /**
     * Le tableau de confrontation, appui par appui, pour un ensemble de
     * dossiers. Trié par tension décroissante : ce qui manque le plus remonte
     * en tête, puisque c'est là que l'arbitrage se joue.
     *
     * @param  Collection<int, Demande>  $dossiers
     */
    public function confrontation(Collection $dossiers): Collection
    {
        return $this->attributions->confrontationDemande($dossiers);
    }

    /**
     * Ce que les attributions déjà prononcées ont réservé sur le stock, appui par appui. Seuls
     * les bons non encore sortis du magasin comptent : une fois servis, leur
     * quantité a déjà quitté `quantite_en_stock` et la déduire à nouveau la
     * compterait deux fois.
     *
     * @param  Collection<int, double>  $idsAppuis
     * @return Collection<int, double>
     */
    public function quantitesEngagees(Collection $idsAppuis): Collection
    {
        return LigneAttribution::query()
            ->whereIn('id_appui', $idsAppuis)
            ->whereHas('attribution', fn ($q) => $q->whereNull('date_destockage'))
            ->selectRaw('id_appui, SUM(quantite_attribuee) AS total')
            ->groupBy('id_appui')
            ->pluck('total', 'id_appui');
    }
}
