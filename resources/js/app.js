import './formulaire-etapes';
import './hors-ligne';

/*
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
*/

/*
 * Tableaux qui se replient en cartes sur petit écran (classe « tableau-carte »,
 * cf. resources/css/app.css) : chaque cellule reprend l'en-tête de sa colonne.
 */
document.querySelectorAll('table.tableau-carte').forEach((tableau) => {
    const entetes = [...tableau.querySelectorAll('thead th')].map((th) => th.textContent.trim());

    tableau.querySelectorAll('tbody tr').forEach((ligne) => {
        [...ligne.children].forEach((cellule, i) => {
            if (cellule.colSpan === 1 && entetes[i]) {
                cellule.dataset.label = entetes[i];
            }
        });
    });
});
