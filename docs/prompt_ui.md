# ROLE ET OBJECTIF
Tu es un UI/UX Designer Senior et Développeur Frontend Expert. 
Génère l'interface utilisateur complète (maquettes, composants et flux d'écrans) pour une application web de gestion, suivi et attribution des appuis aux ressources agricoles pour l'agence ADERIZ (Filière Riz).

# DESIGN SYSTEM & CHARTE VISUELLE
- Thème principal : Agrotech professionnel, épuré et institutionnel.
- Palette de couleurs : 
  - Primaire : Vert Feuille / Forêt (#1B4D3E ou #2E7D32)
  - Secondaire : Ambre / Terre (#D97706)
  - Neutres : Fond gris très clair (#F8FAFC), Cartes blanches (#FFFFFF), Texte foncé (#0F172A)
  - Statuts : Vert (Validé/Octroyé), Rouge (Rejeté), Orange (En cours/En commission), Bleu (Transmis/Délégué)
- Typographie : Sans-serif moderne (Inter ou Roboto).
- Layout général : Sidebar fixe à gauche, Topbar fixe avec notifications/profil, zone de contenu scrollable.

---
### MODULE 1 : PORTAIL ACTEUR DE LA FILIÈRE RIZ (Demandeur / Usager)

#### Écran 1 : Inscription & Connexion
- **Cible :** Acteurs de la filière riz (Producteurs, Coopératives, Entreprises) & Agents ADERIZ.
- **Composants UI :**
  - Formulaire épuré avec deux onglets : **Connexion** / **Inscription**.
  - Champs d'inscription : Nom, Prénom, Numéro de Téléphone / Email, Mot de passe.
  - Option de connexion rapide via Numéro de téléphone (Code OTP/SMS).

#### Écran 2 : Tableau de Bord Acteur
- **Cible :** Demandeur connecté.
- **Composants UI :**
  - **En-tête :** Message de bienvenue nominatif + Indication de la Campagne agricole active (ex: *Campagne 2026-2027*).
  - **KPI Cards :** Demandes en cours, Appuis validés/obtenus, Superficie totale sous exploitation ($ha$).
  - **Bouton d'action principal (CTA Hero) :** `+ Faire une nouvelle demande d'appui`
  - **Tableau des demandes récentes :** Code dossier (ex: `DMD-2026-089`), Date, Résumé des appuis demandés, Badge de statut coloré et bouton "Voir le suivi".

#### Écran 3 : Formulaire de Demande d'Appui Dynamique (Stepper 4 Étapes)
> *Note UX : L'organigramme ADERIZ (Directions/Services) est totalement transparent pour l'usager.*

- **Étape 1 : Localisation & Parcelles**
  - Sélecteur de la Campagne Agricole.
  - Localisation administrative : Région, Département, Localité/Village.
  - Superficie totale à exploiter ($ha$).
  - Ventilation de la superficie par typologie rizicole :
    - Riziculture irriguée ($ha$)
    - Riziculture de bas-fond ($ha$)
    - Riziculture de plateau ($ha$)
  - Date de début souhaitée pour la mise en place des appuis.
  - Zone d'upload pour Photos de la parcelle.

- **Étape 2 : Profil de l'Entité & Documents Légaux (Formulaire Conditionnel)**
  - Sélecteur du statut juridique : `[ Producteur individuel | Groupement informel | Coopérative | Entreprise agricole / ONG ]`
  - *Affichage conditionnel des champs selon le choix :*
    - **Producteur individuel :** Aucun champ supplémentaire requis.
    - **Groupement informel :** Tableau dynamique "Membres du groupement" (Nom, Contact, Superficie de leur parcelle, Type de riziculture).
    - **Coopérative :** Tableau des membres + Zone d'upload obligatoire pour le *Récépissé* ou *l'Agrément de la coopérative* (PDF/Image).
    - **Entreprise agricole / ONG :** Tableau des acteurs/producteurs rattachés + Zone d'upload pour la *Demande-projet adressée à l'ADERIZ* (PDF) + Zone d'upload pour les *Documents de constitution formelle* (PDF).

- **Étape 3 : Panier d'Appuis (Catalogue Unifié)**
  - Moteur de recherche et filtres par catégories de besoins : Intrants/Semences, Équipements/Engins, Aménagements/Infrastructures, Formations.
  - Grille de cartes d'appuis issues de la BDD : Nom de la ressource, photo/icône, unité de mesure (ex: *Kg, Litre, Hectare, Unité*), et champ de saisie de la quantité souhaitée.
  - *Composant Panier :* Sidebar ou bloc récapitulatif listant l'ensemble des appuis sélectionnés avec possibilité d'ajuster les quantités.

- **Étape 4 : Récapitulatif & Validation**
  - Synthèse globale du dossier : Informations parcelle, profil entité, pièces jointes et liste des appuis du panier.
  - Case à cocher d'attestation sur l'honneur.
  - Bouton principal : `Soumettre la demande à l'ADERIZ`.

#### Écran 4 : Suivi Détaillé et Historique d'une Demande (Vue Usager)
- **En-tête :** Numéro de dossier, Date de soumission, Campagne.
- **Timeline / Stepper de progression vertical :**
  1. 🟢 **Demande soumise** (Date & Heure)
  2. 🔵 **En cours d'examen interne** (Service attribué)
  3. 🟠 **Évaluation / Visite de terrain** (Statut de l'instruction)
  4. 🟠 **Examen en Commission d'attribution**
  5. ⚪ **Décision finale & Mise à disposition**
- **Bannière de Rejet (Conditionnel) :** En cas de refus, affichage d'un encadré rouge mettant en valeur le **Motif explicite du rejet** rédigé par l'agent, avec lien de contact du support.

---

## MODULE 2 : WORKFLOW INTERNE & INSTRUCTION ADERIZ

### Écran 5 : Tableau de Bord Interne (Directeurs & Chefs de Service)
- KPI Cards : Demandes reçues par domaine (Infrastructure / Production / Valorisation), Dossiers à affecter, Visites à effectuer, Alertes stock.
- Graphiques de répartition des demandes par Région / Département et par Type d'appui.

### Écran 6 : Interface Directeur de Domaine (Affectation aux Services)
- Cible : Directeur Infra / Directeur Production / Directeur Valorisation.
- Contenu :
  - Liste des demandes entrantes contenant une composante de son domaine.
  - Formulaire modal / Drawer "Affecter aux services" :
    - Permet au Directeur de cocher/sélectionner le ou les **Services internes** de sa direction responsables du traitement (ex: Service Mécanisation, Service Aménagement, etc.).
    - Bouton "Transmettre la demande aux Chefs de Service".

### Écran 7 : Interface Traitement & Instruction par le Chef de Service (Spécifique par Domaine)
- Vue scindée :
  - **Gauche :** Consultation de la demande usager (Parcelles, pièces PDF, photos, membres).
  - **Droite :** Onglets de travail / Formulaire d'instruction :

  *Sous-onglet A : Infrastructure (Si composante Infrastructure)*
  - Champ texte : "Résumé de la prise de contact".
  - Champ texte : "Rapport / Commentaire après visite de site".
  - Sélecteur d'appuis BDD + Champ "Commentaires / Travaux supplémentaires".
  - Sélecteur & Checkbox : "Assigner un Prestataire de service (BDD)".

  *Sous-onglet B : Production (Si composante Production)*
  - Champ texte : "Résumé de la prise de contact".
  - Champ texte : "Rapport / Commentaire après visite de site".
  - Sélecteur d'appuis matériels BDD + Commentaire de précision.
  - Sélecteur de Prestataire délégué pour exécution (BDD).
  - **Section Gestion du Crédit :**
    - Checkbox : "Appui octroyé à crédit ?"
    - Champ Date : "Date d'échéance de remboursement" (Conditionnel si à crédit).

---

## MODULE 3 : COMMISSIONS, STOCKS & ATTRIBUTIONS

### Écran 8 : Interface de Décision des Commissions
- Référence de la commission existante.
- Synthèse des dossiers soumis pour validation finale.
- Zone de décision : Bouton Vert "VALIDER LA COMMISSION", Bouton Rouge "REJETER LA COMMISSION", Champ texte "Motif du rejet".

### Écran 9 : Gestion du Catalogue des Appuis & Stock
- Table des appuis (Infrastructure, Intrants, Matériels) : Libellé, Unité de mesure, Stock disponible, Coût unitaire, Dose préconisée/ha.

### Écran 10 : Attributions & Génération des Bon d'Octroi
- Formulaire d'octroi définitif, décrémentation des stocks et impression du bon de livraison / contrat de crédit avec échéance.