<?php

namespace App\Services;

use App\Enums\StatutAttributionEnum;
use App\Models\Attribution;
use App\Models\Demande;
use App\Models\LigneAttribution;
use Illuminate\Support\Collection;

/**
 * Ce que la commission a sous les yeux au moment d'arbitrer : les dossiers mûrs
 * et, pour chaque appui, la confrontation entre ce qui est demandé et ce qui
 * reste réellement disponible.
 *
 * La logique vit ici plutôt que dans les contrôleurs parce que trois écrans
 * l'utilisent — la liste des demandes, l'écran d'attribution et la synthèse
 * imprimable — et qu'ils doivent tous compter de la même façon.
 */
class SyntheseCommissionService
{
    /**
     * Les dossiers en attente d'arbitrage : instruits favorablement par tous
     * les services concernés, ni rejetés ni déjà attribués.
     *
     * @return Collection<int, Demande>
     */
    public function dossiersEnAttente(): Collection
    {
        return Attribution::query()
            ->with([
                'demande',
                'lignesAttributions',
            ])->where('id_statut_attribution', StatutAttributionEnum::soumise)
            ->orderBy('id_attribution')
            ->get()
            ->values();
    }

    /**
     * Le tableau de confrontation, appui par appui, pour un ensemble de
     * dossiers. Trié par tension décroissante : ce qui manque le plus remonte
     * en tête, puisque c'est là que l'arbitrage se joue.
     *
     * @param  Collection<int, Demande>  $dossiers
     * @return Collection<int, array{libelle: string, unite: ?string, service: ?string, demandee: int, stock: int, engagee: int, disponible: int, couverte: bool, nb_dossiers: int}>
     */
    public function confrontation(Collection $dossiers): Collection
    {
        $lignes = $dossiers->flatMap->lignesAttributions;
        if ($lignes->isEmpty()) {
            return collect();
        }

        $engagees = $this->quantitesEngagees($lignes->pluck('id_appui')->unique());

        return $lignes
            ->groupBy('id_appui')
            ->map(function (Collection $lignesDeLAppui, $idAppui) use ($engagees) {
                $appui = $lignesDeLAppui->first()->appui;

                $demandee = (int) $lignesDeLAppui->sum('quantite_demandee');
                $stock = (int) ($appui?->quantite_en_stock ?? 0);
                $engagee = (int) ($engagees[$idAppui] ?? 0);
                $disponible = $stock - $engagee;

                return [
                    'libelle' => $appui?->libelle ?? 'Appui inconnu',
                    'unite' => $appui?->uniteAppui?->lib_unite_appui,
                    'service' => $appui?->typeAppui?->service?->lib_service,
                    'demandee' => $demandee,
                    'stock' => $stock,
                    'engagee' => $engagee,
                    'disponible' => $disponible,
                    'couverte' => $disponible >= $demandee,
                    'nb_dossiers' => $lignesDeLAppui->count(),
                ];
            })
            ->sortBy([
                // Les appuis en tension d'abord, puis les plus demandés.
                fn ($ligne) => $ligne['couverte'] ? 1 : 0,
                fn ($ligne) => -$ligne['demandee'],
            ])
            ->values();
    }

    public function confrontationDemande(Collection $dossiers): Collection
    {
        $lignes = $dossiers->flatMap->lignes;
        if ($lignes->isEmpty()) {
            return collect();
        }

        $engagees = $this->quantitesEngagees($lignes->pluck('id_appui')->unique());

        return $lignes
            ->groupBy('id_appui')
            ->map(function (Collection $lignesDeLAppui, $idAppui) use ($engagees) {
                $appui = $lignesDeLAppui->first()->appui;

                $demandee = (int) $lignesDeLAppui->sum('quantite_demandee');
                $stock = (int) ($appui?->quantite_en_stock ?? 0);
                $engagee = (int) ($engagees[$idAppui] ?? 0);
                $disponible = $stock - $engagee;

                return [
                    'libelle' => $appui?->libelle ?? 'Appui inconnu',
                    'unite' => $appui?->uniteAppui?->lib_unite_appui,
                    'service' => $appui?->typeAppui?->service?->lib_service,
                    'demandee' => $demandee,
                    'stock' => $stock,
                    'engagee' => $engagee,
                    'disponible' => $disponible,
                    'couverte' => $disponible >= $demandee,
                    'nb_dossiers' => $lignesDeLAppui->count(),
                ];
            })
            ->sortBy([
                // Les appuis en tension d'abord, puis les plus demandés.
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
     * @param  Collection<int, int>  $idsAppuis
     * @return Collection<int, int>
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
