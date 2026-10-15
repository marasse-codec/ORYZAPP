<?php

namespace App\Domain\Suivi\Services;

use App\Domain\Referentiel\Models\Campagne;
use App\Domain\Suivi\Support\FiltreStatistique;
use Illuminate\Support\Collection;

/**
 * La comparaison entre campagnes : les mêmes indicateurs, calculés de la même
 * façon (et avec le même filtre) pour chaque campagne, rangés de la plus
 * ancienne à la plus récente, avec l'évolution par rapport à la précédente.
 *
 * C'est encore de la statistique de gestion : on compare des chiffres que
 * l'application calcule déjà. Une analyse BI irait plus loin (croiser des
 * sources externes, expliquer les écarts, prévoir) ; cf. docs/roadmap-gestion.md.
 */
class ComparaisonCampagnesService
{
    /** Les indicateurs comparés : clé => [libellé, unité]. */
    public const INDICATEURS = [
        'soumises' => ['Demandes soumises', ''],
        'taux_attribution' => ["Taux d'attribution", '%'],
        'beneficiaires' => ['Bénéficiaires servis', ''],
        'superficie_traitee' => ['Superficie traitée', 'ha'],
        'taux_traitement' => ['Superficie traitée / concernée', '%'],
        'delai_arbitrage' => ['Délai soumission → arbitrage', 'j'],
        'rendement' => ['Rendement moyen', 't/ha'],
        'du' => ['Montant dû', 'F'],
        'taux_recouvrement' => ['Taux de recouvrement', '%'],
    ];

    public function __construct(private readonly StatistiqueCampagneService $stats) {}

    /**
     * Une ligne par campagne : ses indicateurs et, pour chacun, l'évolution en %
     * par rapport à la campagne précédente de la liste (null pour la première).
     *
     * @param  Collection<int, Campagne>  $campagnes
     * @return Collection<int, array{campagne: Campagne, valeurs: array<string, ?float>, evolutions: array<string, ?float>}>
     */
    public function comparer(Collection $campagnes, ?FiltreStatistique $filtre = null): Collection
    {
        $precedentes = null;

        return $campagnes
            ->sortBy('mois_debut_campagne')
            ->values()
            ->map(function (Campagne $campagne) use ($filtre, &$precedentes) {
                $valeurs = $this->valeurs($campagne, $filtre);

                $evolutions = collect($valeurs)
                    ->map(fn (?float $valeur, string $cle) => $this->evolution($precedentes[$cle] ?? null, $valeur))
                    ->all();

                $precedentes = $valeurs;

                return ['campagne' => $campagne, 'valeurs' => $valeurs, 'evolutions' => $evolutions];
            });
    }

    /** Les indicateurs d'une campagne, réduits à des nombres (null = pas de donnée). */
    public function valeurs(Campagne $campagne, ?FiltreStatistique $filtre = null): array
    {
        $indicateurs = $this->stats->indicateurs($campagne, $filtre);
        $finances = $this->stats->finances($campagne, $filtre);
        $rendements = $this->stats->rendementsParEcologie($campagne, $filtre);

        // Rendement global pondéré : toutes écologies confondues, production ÷ superficie récoltée.
        $recoltee = $rendements->sum('recoltee');

        return [
            'soumises' => (float) $indicateurs['soumises'],
            'taux_attribution' => $indicateurs['taux_attribution'],
            'beneficiaires' => (float) $indicateurs['beneficiaires'],
            'superficie_traitee' => $indicateurs['superficie_traitee'],
            'taux_traitement' => $indicateurs['taux_traitement'],
            'delai_arbitrage' => $indicateurs['delai_arbitrage'],
            'rendement' => $recoltee > 0 ? round($rendements->sum('production') / $recoltee, 2) : null,
            'du' => $finances['du'],
            'taux_recouvrement' => $finances['taux_recouvrement'],
        ];
    }

    /** Variation en % d'une campagne à la suivante ; null si on ne peut pas la calculer (pas de donnée, ou départ à 0). */
    public function evolution(?float $avant, ?float $apres): ?float
    {
        if ($avant === null || $apres === null || $avant == 0.0) {
            return null;
        }

        return round(($apres - $avant) / abs($avant) * 100, 1);
    }
}
