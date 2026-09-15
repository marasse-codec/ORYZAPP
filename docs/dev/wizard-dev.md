📚 Guide de Conception et d'Implémentation des Wizards de Demandes - ADERIZ
Partie 1 : Fonctionnement du Wizard (Blade + Alpine.js)

Le Wizard repose sur une architecture hybride léger :

    Blade (Laravel) : Rendu côté serveur, injection des données initiales ($acteurs, $appuis, $typesCultures), génération du token CSRF et gestion de la soumission de formulaires classiques.

    Alpine.js : Gestion de l'état réactif côté client (step, tableaux dynamiques, affichage conditionnel des champs et validation avant progression).

1. Structure de l'État Alpine (x-data)

L'état réactif d'un wizard contient :

    Le numéro de l'étape courante (step).

    Le nombre total d'étapes (totalSteps).

    La variante/branche sélectionnée (ex: typeEntite).

    Les tableaux réactifs pour les lignes dynamiques (membres du groupement, appuis matériels, etc.).

JavaScript

x-data="{
    step: 1,
    totalSteps: 4,
    typeEntite: 'producteur_individuel', // Type d'entité sélectionné
    
    // Méthodes de navigation
    nextStep() { if (this.step < this.totalSteps) this.step++; },
    prevStep() { if (this.step > 1) this.step--; },

    // Lignes dynamiques (Ex: Acteurs / Parcelles)
    membres: [
        { id_acteur: '', superficie: '', type_parcelle: '' }
    ],
    addMembre() {
        this.membres.push({ id_acteur: '', superficie: '', type_parcelle: '' });
    },
    removeMembre(index) {
        if (this.membres.length > 1) this.membres.splice(index, 1);
    }
}"

2. Bonnes pratiques pour un Wizard réussi

    Persistance des champs (old()) : Toujours lier les valeurs initiales Alpine avec l'helper Blade old() pour éviter que l'utilisateur ne perde sa saisie si le serveur renvoie une erreur de validation.

    Nomenclature des champs sous forme de tableaux : Pour soumettre des données répétitives à Laravel, nommez les inputs sous forme de matrice :

        Ex : name="membres[0][id_acteur]"

        Ex : name="appuis[1][id_appui]"

    Uploads de fichiers en multi-étapes : Conserver la balise <form> globale autour de toutes les étapes du wizard avec enctype="multipart/form-data". Même si les fichiers sont ajoutés à l'étape 2, ils ne seront envoyés que lors du submit final à l'étape 4.

    Validation pas-à-pas (Client-side validation) : Ne permettez le passage à l'étape suivante (nextStep()) que si les champs obligatoires de l'étape courante sont valides.