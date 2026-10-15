<?php

namespace App\Domain\Suivi\Services;

use App\Domain\Commission\Models\Attribution;
use App\Domain\Commission\Services\ReglementService;
use App\Domain\Demande\Models\Demande;
use App\Domain\Referentiel\Models\Campagne;

/**
 * Le tableau de pilotage du directeur : en une page, où en est la campagne par
 * rapport à ses objectifs et à la campagne précédente, et ce qui bloque
 * (dossiers en attente, stocks bas, impayés). Lecture seule.
 */
class PilotageService
{
    public function __construct(
        private readonly StatistiqueCampagneService $stats,
        private readonly ComparaisonCampagnesService $comparaison,
        private readonly StatistiqueService $suivi,
        private readonly ReglementService $reglements,
    ) {}

    public function tableau(Campagne $campagne): array
    {
        $indicateurs = $this->stats->indicateurs($campagne);
        $finances = $this->stats->finances($campagne);
        $precedente = $this->precedente($campagne);

        return [
            'indicateurs' => $indicateurs,
            'finances' => $finances,
            'objectifs' => $this->stats->objectifs($campagne, $indicateurs, $finances),
            'precedente' => $precedente,
            'evolutions' => $precedente ? $this->evolutions($campagne, $precedente) : [],
            'vigilance' => $this->vigilance(),
        ];
    }

    /** La campagne qui a commencé juste avant celle-ci, s'il y en a une. */
    public function precedente(Campagne $campagne): ?Campagne
    {
        return Campagne::where('mois_debut_campagne', '<', $campagne->mois_debut_campagne)
            ->orderByDesc('mois_debut_campagne')
            ->first();
    }

    /** L'évolution de chaque indicateur par rapport à la campagne précédente, en %. */
    private function evolutions(Campagne $campagne, Campagne $precedente): array
    {
        $avant = $this->comparaison->valeurs($precedente);
        $apres = $this->comparaison->valeurs($campagne);

        return collect($apres)
            ->map(fn (?float $valeur, string $cle) => $this->comparaison->evolution($avant[$cle] ?? null, $valeur))
            ->all();
    }

    /** Ce qui demande l'attention de la direction, toutes campagnes confondues. */
    private function vigilance(): array
    {
        return [
            'a_arbitrer' => Demande::aArbitrer()->count(),
            'a_publier' => Attribution::aPublier()->count(),
            'a_retirer' => Attribution::aDestocker()->count(),
            'stocks_bas' => $this->suivi->stock()['alertes']->count(),
            'acteurs_en_impaye' => $this->reglements->acteursEnImpaye()->count(),
        ];
    }
}
