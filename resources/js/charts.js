import Chart from 'chart.js/auto';

/**
 * Affiche chaque <canvas data-chart='{...}'> avec sa configuration Chart.js,
 * construite côté serveur : les vues décrivent le graphique, ce module ne fait
 * que le dessiner.
 */
document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
    new Chart(canvas, JSON.parse(canvas.dataset.chart));
});
