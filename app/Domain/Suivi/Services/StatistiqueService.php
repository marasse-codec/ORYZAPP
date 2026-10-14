<?php

namespace App\Domain\Suivi\Services;

use App\Domain\Acces\Enums\RoleEnum;
use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Models\SessionCommission;
use App\Domain\Commission\Models\SuperficieAttribution;
use App\Domain\Demande\Enums\StatutDemandeE;
use App\Domain\Demande\Models\Demande;
use App\Domain\Demande\Models\SystemeProduction;
use App\Domain\Stock\Models\Appui;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Les chiffres du tableau de bord.
 *
 * Lecture seule : aucune écriture, donc aucune transaction à ouvrir. Chaque
 * méthode est une requête agrégée côté SQL : on ne charge jamais les dossiers
 * pour les compter en mémoire.
 */
class StatistiqueService
{
    /**
     * Les demandes qu'un utilisateur a le droit de compter.
     * Le DP ne voit que celles qu'il a émises ; l'agent de service, celles de
     * son service (cf. Demande::scopePourUtilisateur) ; les autres, tout.
     */
    private function demandesDe(User $user): Builder
    {
        if ($user->hasRole(RoleEnum::dp->value)) {
            return Demande::query()->where('id_user', $user->id);
        }

        return Demande::query()->pourUtilisateur();
    }

    /**
     * Nombre de demandes par statut, tous statuts présents (0 par défaut).
     *
     * @return array<int, int> valeur de StatutDemandeE => total
     */
    public function demandesParStatut(User $user): array
    {
        $comptes = $this->demandesDe($user)
            ->selectRaw(Demande::statutSql().' AS code_statut, COUNT(*) AS total')
            ->groupBy('code_statut')
            ->pluck('total', 'code_statut');

        return collect(StatutDemandeE::cases())
            ->mapWithKeys(fn (StatutDemandeE $statut) => [$statut->value => (int) ($comptes[$statut->value] ?? 0)])
            ->all();
    }

    /**
     * L'identifiant de la dernière demande (la plus récente) de chaque statut.
     *
     * @return array<int, int|null> valeur de StatutDemandeE => id_demande, null s'il n'y en a aucune
     */
    public function derniereDemandeParStatut(User $user): array
    {
        return collect(StatutDemandeE::cases())
            ->mapWithKeys(fn (StatutDemandeE $statut) => [
                $statut->value => $this->demandesDe($user)
                    ->whereRaw('('.Demande::statutSql().') = ?', [$statut->value])
                    ->orderByDesc('demandes.id_demande')
                    ->value('demandes.id_demande'),
            ])
            ->all();
    }

    /**
     * Demandes soumises par campagne.
     *
     * @return Collection<string, int> libellé de campagne => total
     */
    public function demandesParCampagne(User $user): Collection
    {
        return $this->demandesDe($user)
            ->whereNotNull('date_soummission')
            ->join('campagnes', 'campagnes.id_campagne', '=', 'demandes.id_campagne')
            ->selectRaw('campagnes.lib_campagne AS campagne, COUNT(*) AS total')
            ->groupBy('campagnes.id_campagne', 'campagnes.lib_campagne')
            ->orderBy('campagnes.mois_debut_campagne')
            ->pluck('total', 'campagne');
    }

    /**
     * Les attributions que l'utilisateur peut voir (cf. Attribution::scopeUser),
     * de la plus récente à la plus ancienne.
     *
     * @return Collection<int, Attribution>
     */
    public function dernieresAttributions(int $limite = 5): Collection
    {
        return Attribution::query()
            ->user()
            ->with('demande.acteur')
            ->latest('id_attribution')
            ->limit($limite)
            ->get();
    }

    /**
     * Superficies dans le périmètre de l'utilisateur : déclarée par les demandes soumises (concernée)
     * et traitable selon les attributions (traitée).
     *
     * @return array{concernee: float, traitee: float, taux: ?float}
     */
    public function superficies(User $user): array
    {
        $demandes = fn () => $this->demandesDe($user)->whereNotNull('date_soummission')->select('demandes.id_demande');

        $concernee = (float) SystemeProduction::whereIn('id_demande', $demandes())->sum('superficie');
        $traitee = (float) SuperficieAttribution::whereHas('attribution', fn ($q) => $q->whereIn('id_demande', $demandes()))->sum('superficie_traitee');

        return [
            'concernee' => $concernee,
            'traitee' => $traitee,
            'taux' => $concernee > 0 ? round($traitee / $concernee * 100, 1) : null,
        ];
    }

    /** Tableau de bord de la commission : ce qui attend son arbitrage ou sa publication. */
    public function commission(): array
    {
        return [
            'sessions_ouvertes' => SessionCommission::whereNull('date_fin_session')->count(),
            'demandes_a_arbitrer' => Demande::aArbitrer()->count(),
            'attributions_a_publier' => Attribution::aPublier()->count(),
        ];
    }

    /** Tableau de bord du magasin : alertes de stock et sorties à effectuer. */
    public function stock(): array
    {
        // Seuil à 0 = pas d'alerte voulue pour cet appui.
        $alertes = Appui::query()
            ->where('seuil_alerte', '>', 0)
            ->with('typeAppui')
            ->get()
            ->map(fn (Appui $appui) => [
                'appui' => $appui,
                'stock' => $appui->quantiteeEnStock(),
            ])
            ->filter(fn (array $ligne) => $ligne['stock'] <= $ligne['appui']->seuil_alerte)
            ->sortBy('stock')
            ->values();

        return [
            'alertes' => $alertes,
            'sorties_a_effectuer' => Attribution::aDestocker()->count(),
        ];
    }
}
