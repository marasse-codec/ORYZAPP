<?php

namespace App\Domain\Suivi\Support;

/**
 * Configurations Chart.js prêtes à poser dans un <canvas data-chart="...">
 * (cf. resources/js/charts.js). Les vues restent sans logique de graphique.
 */
class Graphique
{
    /**
     * @param  array<int, string>  $libelles
     * @param  array<int, int|float>  $valeurs
     * @param  array<int, string>  $couleurs
     */
    public static function anneau(array $libelles, array $valeurs, array $couleurs): array
    {
        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $libelles,
                'datasets' => [['data' => $valeurs, 'backgroundColor' => $couleurs]],
            ],
            'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'bottom']]],
        ];
    }

    /**
     * Barres, verticales ou horizontales ($horizontal), axe des valeurs à partir de 0.
     * Chaque série : ['label' => string, 'data' => array, 'color' => string].
     * La légende n'apparaît que s'il y a plusieurs séries. $decimales : les graduations de l'axe
     * ne se limitent plus aux entiers (rendements en t/ha).
     *
     * @param  array<int, string>  $libelles
     * @param  array<int, array{label: string, data: array<int, int|float>, color: string}>  $series
     */
    public static function barres(array $libelles, array $series, bool $horizontal = false, bool $decimales = false): array
    {
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $libelles,
                'datasets' => array_map(fn (array $serie) => [
                    'label' => $serie['label'],
                    'data' => $serie['data'],
                    'backgroundColor' => $serie['color'],
                ], $series),
            ],
            'options' => [
                'indexAxis' => $horizontal ? 'y' : 'x',
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => count($series) > 1, 'position' => 'bottom']],
                'scales' => [($horizontal ? 'x' : 'y') => ['beginAtZero' => true, 'ticks' => ['precision' => $decimales ? 2 : 0]]],
            ],
        ];
    }

    /**
     * Courbes : une par série, un point par libellé (ex. une campagne). Un point sans
     * donnée (null) laisse un trou plutôt qu'un 0 trompeur.
     *
     * @param  array<int, string>  $libelles
     * @param  array<int, array{label: string, data: array<int, int|float|null>, color: string}>  $series
     */
    public static function courbes(array $libelles, array $series): array
    {
        return [
            'type' => 'line',
            'data' => [
                'labels' => $libelles,
                'datasets' => array_map(fn (array $serie) => [
                    'label' => $serie['label'],
                    'data' => $serie['data'],
                    'borderColor' => $serie['color'],
                    'backgroundColor' => $serie['color'],
                    'tension' => 0.2,
                    'spanGaps' => false,
                ], $series),
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['position' => 'bottom']],
                'scales' => ['y' => ['beginAtZero' => true]],
            ],
        ];
    }
}
