/*
 * Formulaires en plusieurs étapes (demande d'appui) : on ne passe à l'étape suivante
 * que si les champs obligatoires de l'étape sont remplis, et à l'envoi, un champ
 * manquant ramène à l'étape où il se trouve.
 *
 * Chaque étape est un bloc marqué data-etape="N". Les champs obligatoires portent
 * l'attribut HTML « required » (ou un message posé avec setCustomValidity).
 */
export const FormulaireEtapes = {
    /** Vrai si les champs de l'étape sont valides ; sinon, signale le premier et rend faux. */
    etapeValide(racine, etape) {
        const bloc = racine.querySelector(`[data-etape="${etape}"]`);
        if (!bloc) {
            return true;
        }

        const invalide = [...bloc.querySelectorAll('input, select, textarea')].find((champ) => !champ.checkValidity());
        if (invalide) {
            invalide.reportValidity();
            return false;
        }

        return true;
    },

    /** Le premier champ invalide du formulaire et son étape, ou null si tout est rempli. */
    premierInvalide(formulaire) {
        const champ = [...formulaire.elements].find((element) => element.willValidate && !element.checkValidity());
        if (!champ) {
            return null;
        }

        const bloc = champ.closest('[data-etape]');

        return { champ, etape: bloc ? Number(bloc.dataset.etape) : null };
    },
};

window.FormulaireEtapes = FormulaireEtapes;
