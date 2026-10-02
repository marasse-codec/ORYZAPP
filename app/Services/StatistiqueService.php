<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Appui;
use App\Models\Attribution;
use App\Models\Demande;
use App\Models\SessionCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Les chiffres du tableau de bord.
 *
 * Lecture seule : aucune écriture, donc aucune transaction à ouvrir. Chaque
 * méthode est une requête agrégée côté SQL — on ne charge jamais les dossiers
 * pour les compter en mémoire.
 */
class StatistiqueService
{
    /**
     * L'état d'une demande ne se stocke pas, il se déduit de ses colonnes.
     * Cette expression en est la seule définition côté SQL : une demande
     * attribuée prime sur un rejet ancien, qui prime sur le brouillon.
     */
    private const ETAT_DEMANDE = "CASE
        WHEN EXISTS (SELECT 1 FROM attributions WHERE attributions.id_demande = demandes.id_demande) THEN 'attribuee'
        WHEN motifs_rejet IS NOT NULL THEN 'rejetee'
        WHEN date_soummission IS NULL THEN 'brouillon'
        ELSE 'en_attente'
    END";

    /** Libellés des états, dans l'ordre d'affichage du graphique. */
    public const ETATS = [
        'brouillon' => 'Brouillons',
        'en_attente' => 'En attente d\'arbitrage',
        'attribuee' => 'Attribuées',
        'rejetee' => 'Rejetées',
    ];

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
     * Nombre de demandes par état, tous états présents (0 par défaut).
     *
     * @return array<string, int>
     */
    public function demandesParEtat(User $user): array
    {
        $comptes = $this->demandesDe($user)
            ->selectRaw(self::ETAT_DEMANDE.' AS etat, COUNT(*) AS total')
            ->groupBy('etat')
            ->pluck('total', 'etat');

        return collect(self::ETATS)
            ->map(fn (string $libelle, string $etat) => (int) ($comptes[$etat] ?? 0))
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

    /** Tableau de bord de la commission : ce qui attend son arbitrage ou sa publication. */
    public function commission(): array
    {
        return [
            'sessions_ouvertes' => SessionCommission::whereNull('date_fin_session')->count(),
            // Soumises, pas encore rattachées à une séance ni attribuées (cf. Demande::scopeAttribuables).
            'demandes_a_arbitrer' => Demande::whereNotNull('date_soummission')
                ->whereNull('id_session')
                ->whereDoesntHave('attribution')
                ->count(),
            'attributions_a_publier' => Attribution::whereNull('date_publication')
                ->whereHas('session', fn ($q) => $q->whereNotNull('date_fin_session'))
                ->count(),
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
            // Séance clôturée, bon pas encore retiré au magasin (cf. Attribution::estDestockable).
            'sorties_a_effectuer' => Attribution::whereNull('date_destockage')
                ->whereHas('session', fn ($q) => $q->whereNotNull('date_fin_session'))
                ->count(),
        ];
    }
}
