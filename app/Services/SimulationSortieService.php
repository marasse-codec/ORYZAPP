<?php

namespace App\Services;

use App\Models\Appui;
use App\Models\SessionCommission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Le brouillon d'arbitrage de la commission : ce qu'elle envisage de sortir du
 * magasin, appui par appui, avant d'avoir rien signé.
 *
 * Rien n'est écrit en base — ni attribution, ni décrément de `quantite_en_stock`.
 * Le brouillon vit dans le cache partagé, une entrée par séance : tous les
 * membres qui siègent voient et alimentent le même tableau, ce qui est le
 * propre d'une séance tenue en commun. Un brouillon par navigateur donnerait à
 * chacun des disponibilités différentes au même instant.
 *
 * Une seule quantité par appui, la dernière saisie l'emporte : le tableau
 * reflète la position de la commission, pas la somme des avis de ses membres.
 */
class SimulationSortieService
{
    private const PREFIXE = 'simulation_sorties';

    /**
     * Le brouillon ne survit pas à la séance qui l'a produit : la durée n'est
     * qu'un ramasse-miettes pour les séances qu'on aurait oublié de clôturer.
     */
    private const DUREE = 60 * 60 * 24 * 7;

    /** Au-delà, on considère que le verrou ne viendra pas. */
    private const ATTENTE_VERROU = 3;

    public function __construct(private SyntheseCommissionService $synthese) {}

    /**
     * Un poste par appui quantifiable : ce que les dossiers réclament, ce qui
     * reste réellement disponible, et ce que le brouillon a déjà consommé.
     *
     * Le catalogue entier est servi, pas seulement les appuis réclamés : la
     * commission doit pouvoir simuler une sortie qu'aucun dossier ne porte.
     *
     * @return Collection<int, array{id_appui: int, libelle: string, unite: string, service: ?string, demandee: int, stock: int, engagee: int, disponible: int, simulee: int, restant: int}>
     */
    public function etat(?SessionCommission $seance): Collection
    {
        $appuis = Appui::quantifiables()
            ->with('uniteAppui', 'typeAppui.service')
            ->orderBy('libelle')
            ->get();

        $brouillon = $this->brouillon($seance);
        $demandees = $this->quantitesDemandees();
        $engagees = $this->synthese->quantitesEngagees($appuis->pluck('id_appui'));

        return $appuis
            ->map(function (Appui $appui) use ($brouillon, $demandees, $engagees) {
                $stock = (int) $appui->quantite_en_stock;
                $engagee = (int) ($engagees[$appui->id_appui] ?? 0);
                $disponible = $stock - $engagee;
                $simulee = (int) ($brouillon[$appui->id_appui] ?? 0);

                return [
                    'id_appui' => (int) $appui->id_appui,
                    'libelle' => $appui->libelle,
                    'unite' => $appui->uniteAppui?->lib_unite_appui ?? 'unité',
                    'service' => $appui->typeAppui?->service?->lib_service,
                    'demandee' => (int) ($demandees[$appui->id_appui] ?? 0),
                    'stock' => $stock,
                    'engagee' => $engagee,
                    'disponible' => $disponible,
                    'simulee' => $simulee,
                    'restant' => $disponible - $simulee,
                ];
            })
            ->values();
    }

    /**
     * Les quantités simulées, indexées par appui.
     *
     * La clé porte l'identifiant de la séance : une séance close emporte son
     * brouillon, la suivante démarre sur une table nette plutôt que d'hériter
     * d'arbitrages qui ne sont pas les siens.
     *
     * @return array<int, int>
     */
    public function brouillon(?SessionCommission $seance): array
    {
        if ($seance === null) {
            return [];
        }

        $quantites = Cache::get($this->cle($seance));

        return is_array($quantites) ? $quantites : [];
    }

    /**
     * Note une quantité simulée. Zéro retire le poste du brouillon plutôt que
     * de l'y laisser traîner : un brouillon ne garde que ce qu'on y a mis.
     *
     * La lecture-modification-écriture se fait sous verrou : plusieurs membres
     * saisissent en même temps, et sans lui la dernière écriture effacerait les
     * postes que les autres viennent d'ajouter.
     */
    public function noter(SessionCommission $seance, int $idAppui, int $quantite): void
    {
        Cache::lock($this->cle($seance).':verrou', self::ATTENTE_VERROU + 1)
            ->block(self::ATTENTE_VERROU, function () use ($seance, $idAppui, $quantite) {
                $quantites = $this->brouillon($seance);

                if ($quantite > 0) {
                    $quantites[$idAppui] = $quantite;
                } else {
                    unset($quantites[$idAppui]);
                }

                Cache::put($this->cle($seance), $quantites, self::DUREE);
            });
    }

    /** Vide le brouillon de la séance — pour tous ceux qui y siègent. */
    public function reinitialiser(?SessionCommission $seance): void
    {
        if ($seance !== null) {
            Cache::forget($this->cle($seance));
        }
    }

    private function cle(SessionCommission $seance): string
    {
        return self::PREFIXE.':'.$seance->id_session;
    }

    /**
     * Ce que réclament les dossiers mûrs pour l'arbitrage, appui par appui.
     *
     * Le périmètre est celui de la séance : instruits, validés pour la
     * commission — tous les services concernés ont rendu leur accord —, ni
     * rejetés ni déjà attribués. Un dossier encore à l'instruction n'y figure
     * pas : la commission ne peut rien lui allouer aujourd'hui, et le compter
     * gonflerait une pression sur laquelle elle n'a pas la main.
     *
     * La liste vient de la synthèse : cette maturité se lit dans les rapports
     * d'instruction, pas dans une colonne, donc elle ne se requête pas en SQL.
     * C'est aussi ce qui garantit que la simulation et la synthèse imprimable
     * comptent les mêmes dossiers.
     *
     * @return Collection<int, int>
     */
    private function quantitesDemandees(): Collection
    {
        return $this->synthese
            ->dossiersEnAttente()
            ->flatMap->lignes
            ->groupBy('id_appui')
            ->map(fn (Collection $lignes) => (int) $lignes->sum('quantite_demandee'));
    }
}
