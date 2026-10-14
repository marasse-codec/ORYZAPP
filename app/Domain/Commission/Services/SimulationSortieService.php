<?php

namespace App\Domain\Commission\Services;

use App\Utils\Helper;

use App\Domain\Stock\Models\Appui;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Le brouillon de simulation d'un utilisateur : ce qu'il envisage de sortir du
 * magasin, appui par appui, avant d'avoir rien signé.
 *
 * Rien n'est écrit côté attributions ni côté stock (`quantite_en_stock`), et
 * aucune table n'est dédiée au brouillon : il vit dans le cache, une entrée par
 * utilisateur. Il est personnel (chacun simule de son côté) et sans lien avec
 * les séances : on simule à n'importe quel moment. Il ne disparaît que lorsque
 * son auteur le réinitialise, ou si le cache du serveur est vidé.
 *
 * Une seule quantité par appui : la dernière saisie l'emporte.
 */
class SimulationSortieService
{
    private const PREFIXE = 'simulation_sorties';

    /** Renouvelée à chaque saisie : un brouillon abandonné finit par s'effacer de lui-même. */
    private const DUREE = 60 * 60 * 24 * 30;

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
     * @return Collection<int, array{id_appui: int, libelle: string, unite: string, service: ?string, demandee: float, stock: float, engagee: float, disponible: float, simulee: float, restant: float}>
     */
    public function etat(User $user): Collection
    {
        $appuis = Appui::with('typeAppui.service')
            ->orderBy('libelle')
            ->pourUtilisateur()
            ->get();

        $brouillon = $this->brouillon($user);
        $demandees = $this->quantitesDemandees();
        $engagees = $this->synthese->quantitesEngagees($appuis->pluck('id_appui'));

        return $appuis
            ->map(function (Appui $appui) use ($brouillon, $demandees, $engagees) {
                $stock = Helper::arrondiQuantite($appui->quantite_en_stock);
                $engagee = Helper::arrondiQuantite($engagees[$appui->id_appui] ?? 0);
                $disponible = $stock - $engagee;
                $simulee = Helper::arrondiQuantite($brouillon[$appui->id_appui] ?? 0);

                return [
                    'id_appui' => (int) $appui->id_appui,
                    'libelle' => $appui->libelle,
                    'unite' => $appui->uniteDosage?->lib_unite ?? 'unité',
                    'service' => $appui->typeAppui?->service?->lib_service,
                    'demandee' => Helper::arrondiQuantite($demandees[$appui->id_appui] ?? 0),
                    'stock' => $stock,
                    'engagee' => $engagee,
                    'disponible' => $disponible,
                    'simulee' => $simulee,
                    'restant' => Helper::arrondiQuantite($disponible - $simulee),
                ];
            })
            ->values();
    }

    /**
     * Les quantités simulées par l'utilisateur, indexées par appui.
     *
     * @return array<int, float>
     */
    public function brouillon(User $user): array
    {
        $quantites = Cache::get($this->cle($user));

        return is_array($quantites) ? $quantites : [];
    }

    /**
     * Note une quantité simulée. Zéro retire le poste du brouillon plutôt que
     * de l'y laisser traîner : un brouillon ne garde que ce qu'on y a mis.
     *
     * La lecture-modification-écriture se fait sous verrou : la saisie part au
     * fil de la frappe, et deux envois rapprochés ne doivent pas s'écraser.
     */
    public function noter(User $user, int $idAppui, float $quantite): void
    {
        Cache::lock($this->cle($user).':verrou', self::ATTENTE_VERROU + 1)
            ->block(self::ATTENTE_VERROU, function () use ($user, $idAppui, $quantite) {
                $quantites = $this->brouillon($user);

                if ($quantite > 0) {
                    $quantites[$idAppui] = $quantite;
                } else {
                    unset($quantites[$idAppui]);
                }

                Cache::put($this->cle($user), $quantites, self::DUREE);
            });
    }

    /** Vide le brouillon de l'utilisateur, et de lui seul. */
    public function reinitialiser(User $user): void
    {
        Cache::forget($this->cle($user));
    }

    private function cle(User $user): string
    {
        return self::PREFIXE.':utilisateur:'.$user->id;
    }

    /**
     * Ce que réclament les dossiers mûrs pour l'arbitrage, appui par appui.
     *
     * Le périmètre : dossiers soumis, ni rejetés ni déjà attribués.
     *
     * La liste vient de la synthèse, ce qui garantit que la simulation et la
     * synthèse imprimable comptent les mêmes dossiers.
     *
     * @return Collection<int, int>
     */
    private function quantitesDemandees(): Collection
    {
        return $this->synthese
            ->dossiersEnAttente()
            ->flatMap->lignes
            ->groupBy('id_appui')
            ->map(fn (Collection $lignes) => Helper::arrondiQuantite($lignes->sum('quantite_demandee')));
    }
}
