/*
 * Mode hors-ligne, côté page.
 *
 * 1. Un bandeau prévient quand la connexion est perdue, et disparaît à son retour.
 * 2. « BrouillonLocal » garde une saisie dans le navigateur (localStorage) quand le
 *    serveur ne répond pas ; le formulaire la renvoie au serveur dès le retour du réseau.
 *
 * Rien n'est envoyé au serveur depuis ce fichier : chaque formulaire décide quoi garder.
 */

const PREFIXE = 'oryza.brouillon.';

export const BrouillonLocal = {
    /** Garde des données sous un nom (ex. « demande »), avec l'heure. */
    sauver(nom, donnees) {
        try {
            localStorage.setItem(PREFIXE + nom, JSON.stringify({ donnees, enregistre_le: new Date().toISOString() }));
            return true;
        } catch (e) {
            return false; // navigation privée ou stockage plein : on ne bloque pas la saisie
        }
    },

    /** Relit ce qui a été gardé, ou null. */
    lire(nom) {
        try {
            const texte = localStorage.getItem(PREFIXE + nom);
            return texte ? JSON.parse(texte) : null;
        } catch (e) {
            return null;
        }
    },

    effacer(nom) {
        try {
            localStorage.removeItem(PREFIXE + nom);
        } catch (e) {
            // rien à faire
        }
    },
};

window.BrouillonLocal = BrouillonLocal;

/* Le bandeau « hors ligne » : présent dans le layout, caché tant que la connexion va bien. */
function majBandeau() {
    const bandeau = document.getElementById('bandeau-hors-ligne');
    if (bandeau) {
        bandeau.hidden = navigator.onLine;
    }
}

window.addEventListener('online', majBandeau);
window.addEventListener('offline', majBandeau);
document.addEventListener('DOMContentLoaded', majBandeau);

/*
 * À la déconnexion, les pages gardées pour le hors-ligne sont effacées : sur un
 * appareil partagé, la personne suivante ne doit pas les retrouver.
 */
document.addEventListener('submit', (evenement) => {
    if (evenement.target.action?.endsWith('/logout') && 'caches' in window) {
        caches.keys().then((noms) => noms.filter((nom) => nom.endsWith('-pages')).forEach((nom) => caches.delete(nom)));
        Object.keys(localStorage).filter((cle) => cle.startsWith(PREFIXE)).forEach((cle) => localStorage.removeItem(cle));
    }
});
