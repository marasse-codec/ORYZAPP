# Fiche : les statistiques de campagne

Page **Statistiques** (réservée à qui a la permission « voir-statistiques », l'admin en pratique).
Tous les chiffres portent sur **une campagne**, choisie en haut de page (la campagne en cours par défaut).
Code : `StatistiqueCampagneService` (calculs), `StatistiqueController` (mise en forme), `statistiques/index.blade.php` (affichage).

## Règles communes

- **Périmètre :** les demandes **soumises** (date de soumission renseignée) et rattachées à la campagne.
  Un brouillon n'est pas une demande à satisfaire. Les dons directs, sans demande, n'y figurent pas.
- **Statut d'une demande :** jamais stocké, déduit dans cet ordre :
  1. elle a une attribution → *Reçu attribution* ;
  2. sinon elle a un motif de rejet → *Rejetée* ;
  3. sinon elle n'est pas soumise → *En cours* (brouillon) ;
  4. sinon → *Soumise*.
- **Agrégats en SQL** (COUNT, SUM, GROUP BY). Seuls les délais se calculent en PHP.
- Une valeur sans donnée s'affiche « - », jamais 0 trompeur.

## Chiffres clés (les 8 tuiles)

| Tuile | Calcul |
|---|---|
| **Demandes soumises** | Toutes les demandes de la campagne, brouillons exclus (attribuées + rejetées + en attente). |
| **Attribuées** | Demandes ayant au moins une attribution. Indice : attribuées ÷ soumises × 100 (taux d'attribution). |
| **Rejetées** | Demandes avec un motif de rejet et sans attribution. |
| **Superficie concernée** | Somme des superficies déclarées (par système de production) des demandes soumises. |
| **Superficie traitée** | Somme des superficies que les attributions permettent de traiter (saisies par la commission, par écologie). Indice : traitée ÷ concernée × 100. |
| **Bénéficiaires servis** | Nombre d'acteurs distincts ayant reçu au moins une attribution. |
| **Délai soumission → arbitrage** | Moyenne, en jours de calendrier, entre la date de soumission et la création de l'attribution. |
| **Délai arbitrage → retrait** | Moyenne, en jours de calendrier, entre la création de l'attribution et la date de sortie du magasin. |

Les délais ne comptent que les dossiers qui ont les deux dates. On compare des jours, pas des heures.

## Les graphiques

| Nom affiché | Type | Ce qu'il montre | Calcul |
|---|---|---|---|
| **Demandes par statut** | Anneau | Répartition des demandes de la campagne. | Compte par statut, **brouillons compris** (les quatre statuts sont toujours présents). |
| **Bénéficiaires par type d'acteur** | Anneau | Qui est servi (organisation, coopérative, individuel…). | Demandes **ayant une attribution**, comptées une fois, par type d'acteur de la demande. |
| **Superficies par écologie (ha)** | Barres, 2 séries | Concernée (or) et traitée (vert) pour bas-fond, irrigué, plateau. | Mêmes sommes que les tuiles de superficie, ventilées par type de système de production. |
| **Rendement moyen par écologie (t/ha)** | Barres | Ce que les superficies ont réellement produit, d'après le retour des DP. | **Production totale ÷ superficie récoltée totale** de l'écologie (moyenne pondérée : une grande parcelle pèse plus qu'une petite). Une écologie sans retour vaut 0 sur le graphique. Sous le graphique : « x/y superficie(s) renseignée(s) », avec les tonnes et hectares cumulés. |
| **Types d'appuis : demandés et servis** | Barres, 2 séries | Semences, engrais, matériel… : demande contre offre. | *Demandés* : demandes distinctes qui sollicitent le type. *Servis* : attributions distinctes qui contiennent un appui de ce type. C'est un nombre de dossiers, pas une quantité (les unités diffèrent). |
| **Localités les plus demandeuses** | Barres horizontales | Les 10 localités où l'on demande le plus. | Demandes distinctes par localité (une demande peut couvrir plusieurs localités), triées par total puis par nom. |

Un graphique sans donnée est remplacé par une phrase (« Aucune demande soumise sur cette campagne », etc.).

## Tableau : Satisfaction des quantités demandées

Une ligne par appui :

- **Demandé** : somme des quantités demandées, sur les lignes de demande qui visent cet appui précis
  (une ligne qui ne vise qu'un type d'appui n'est pas comptée).
- **Attribué** : somme des quantités attribuées pour cet appui.
- **Taux** : attribué ÷ demandé × 100 ; « - » si rien n'a été demandé.
- Tri : du plus demandé au moins demandé.

## À savoir pour lire les chiffres

- **« Attribuées » compte dès l'attribution**, même si la séance est encore ouverte ou les résultats non publiés.
- **Le rendement dépend des retours.** Tant que les DP n'ont pas transmis leurs rendements, le graphique est vide ou partiel :
  regardez le « x/y renseignée(s) » avant de conclure.
- **Les rendements sont rattachés à la campagne de la demande**, pas à la date de saisie.
- **Quantités et dossiers ne se mélangent pas :** le graphique des types d'appuis compte des dossiers,
  le tableau de satisfaction des quantités.
