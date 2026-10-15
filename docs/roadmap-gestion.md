# ORYZA : feuille de route d'une application de gestion

Ce document répond à trois besoins :

1. **la feuille de route** : ce qu'une application de gestion doit contenir, ce qu'ORYZA fait déjà, ce que la v2 ajoute et ce qui reste à faire ;
2. **les réponses aux questions** posées sur les statistiques, les agrégats SQL, la BI, les objectifs, les rapports, l'historisation, le hors-ligne, les campagnes, les paramètres généraux et la différence entre tableau de bord et tableau de pilotage ;
3. **les points d'attention** pour la mise en production (schéma modifié, rôles, sécurité).

Les diagrammes de la v2 sont dans [`docs/v2`](v2) et le déroulé complet d'une demande, avec les algorithmes, dans
[`docs/algorithmes-processus.md`](algorithmes-processus.md).

---

## 1. Feuille de route

Légende : ✅ en place avant la v2 · 🆕 ajouté par la v2 · ⏳ à faire plus tard.

### 1.1 Les acteurs

| Acteur | Ce qu'il fait | État |
|---|---|---|
| Administrateur | Comptes, droits, référentiels, paramètres, clôture, santé, journal | ✅ + 🆕 |
| Délégué de pôle (DP) | Saisit les demandes, la visite, les rendements | ✅ |
| Agent de service | Instruit les demandes de son service, consulte ses stocks | ✅ |
| Agent de commission | Séances, attributions, rejets, publication | ✅ |
| Gestionnaire de stock | Approvisionnements, retraits, restitutions, catalogue | ✅ |
| Visiteur (acteur de la filière) | Consulte la vitrine : la démarche, les pièces, le catalogue. **Aucun compte, aucun droit** : le DP saisit sa demande pour lui | ✅ (inscription publique retirée) |
| **Directeur** | Pilote : tableau de pilotage, statistiques, comparaisons, rapports (lecture seule) | 🆕 |
| **Comptable** | Tient la caisse : règlements, impayés, rapport | 🆕 |
| Planificateur (système) | Sauvegardes, rappels, résumé quotidien, signal de vie | ✅ + 🆕 |
| Auditeur / contrôleur (lecture du journal) | Contrôle a posteriori | ⏳ (faisable avec « Rôles et droits ») |
| Bailleur / partenaire | Rapports par financement | ⏳ (demande un champ « financement » sur l'approvisionnement) |
| Fournisseur | Délais et prix d'achat | ⏳ |

Diagrammes de cas d'utilisation, **un par acteur** : `docs/v2/usecases/uc_*.puml`.

### 1.2 Les fonctions d'une application de gestion

| Domaine | Fonction | État |
|---|---|---|
| **Saisie** | Formulaires validés, brouillon automatique, saisie en série | ✅ |
| | Saisie hors ligne (brouillon gardé sur l'appareil) | 🆕 |
| **Processus** | Workflow demande → visite → soumission → arbitrage → publication → retrait → règlement → rendement | ✅ |
| | Clôture de campagne (les chiffres deviennent définitifs) | 🆕 |
| **Accès** | Rôles et permissions | ✅ |
| | Droits modifiables rôle par rôle par l'administrateur | 🆕 |
| | Désactivation de compte au lieu de suppression | 🆕 |
| | Politique de mot de passe réglable, blocage après N tentatives | 🆕 |
| | Double authentification | ⏳ |
| **Pilotage** | Tableau de bord par rôle | ✅ |
| | Tableau de pilotage de la direction | 🆕 |
| | Statistiques filtrées (département, localité, type d'appui, type d'acteur, période) | 🆕 |
| | Comparaison entre campagnes | 🆕 |
| | Finances : engagé, dû, encaissé, taux de recouvrement | 🆕 |
| **Rapports** | Exports Excel, fiches d'arbitrage, bons, courriers | ✅ |
| | Rapport de campagne en PDF | 🆕 |
| | Envoi automatique des rapports par e-mail | ⏳ |
| **Traçabilité** | Journal des actions (qui, quoi, quand) | ✅ |
| | Valeurs avant/après, connexions, exports, adresse IP | 🆕 |
| | Suppression douce des attributions, chronologie d'un dossier | 🆕 |
| **Exploitation** | Sauvegardes automatiques | ✅ |
| | Paramètres généraux, écran de santé du système | 🆕 |
| **Analyse avancée (BI)** | Entrepôt de données, croisements, prévisions | ⏳ (voir § 2.4) |

---

## 2. Réponses aux questions

### 2.1 (Q7) Les agrégats SQL utilisés, expliqués simplement

Un **agrégat** résume plusieurs lignes en une seule valeur. Au lieu de charger 10 000 demandes pour les compter
en PHP, on demande directement le résultat à la base, ce qui est beaucoup plus rapide.

| Agrégat | Ce qu'il fait | Où il sert dans ORYZA |
|---|---|---|
| `COUNT(*)` | Compte les lignes | Nombre de demandes par statut |
| `COUNT(DISTINCT x)` | Compte les valeurs **différentes** de x | Demandes par localité (une demande sur 2 localités ne compte qu'une fois) |
| `COUNT(colonne)` | Compte les lignes où la colonne **n'est pas vide** | Superficies qui ont reçu un rendement |
| `SUM(x)` | Additionne | Superficies, quantités demandées et attribuées, montants, paiements |
| `SUM(CASE WHEN … THEN x ELSE 0 END)` | Additionne **seulement** les lignes qui remplissent une condition | Montant dû = somme des lignes des attributions **publiées** |
| `COALESCE(x, 0)` | Remplace « vide » par 0 | Évite qu'un coût ou une remise vide donne un total vide |
| `GROUP BY` | Fait un sous-total par groupe | Par écologie, par appui, par localité, par type d'acteur |
| `CASE WHEN … END` | Choisit une valeur selon des conditions, dans l'ordre | Le statut d'une demande (voir 2.3) |
| `EXISTS (sous-requête)` | Vrai s'il existe au moins une ligne liée | « La demande a une attribution », filtres par localité ou type d'appui |
| `ORDER BY … LIMIT 10` | Trie puis garde les 10 premiers | Les 10 localités les plus demandeuses |

Deux calculs se font en PHP plutôt qu'en SQL, parce qu'ils doivent rester identiques sur toutes les bases :

- **les délais moyens** : pour chaque dossier, on fait l'écart en jours entre deux dates, puis la moyenne ;
- **les taux** : un taux = partie ÷ total × 100, arrondi à 0,1. Un total nul donne « - » au lieu d'un 0 trompeur.

**Une règle importante : ne jamais joindre deux « côtés multiples » dans le même agrégat.** Une attribution a
plusieurs lignes **et** plusieurs paiements. Si on joint les deux, chaque paiement est répété autant de fois qu'il y a
de lignes, et la somme est fausse. ORYZA calcule donc chaque somme dans une requête séparée, puis rapproche les
résultats en PHP (voir `StatistiqueCampagneService::finances()` et `superficiesParEcologie()`).

### 2.2 (Q5) Les requêtes des statistiques et les filtres

Toutes les statistiques partent de la même requête de base. On l'appelle la **population statistique** :

```sql
-- Les demandes soumises de la campagne choisie (les brouillons sont exclus)
SELECT … FROM demandes
WHERE demandes.id_campagne = :campagne
  AND demandes.date_soummission IS NOT NULL
```

Les **filtres** (`App\Domain\Suivi\Support\FiltreStatistique`) ajoutent des conditions à cette même requête :

```sql
  AND demandes.id_type_acteur = :type_acteur          -- type d'acteur
  AND demandes.date_soummission >= :du                -- période : début
  AND demandes.date_soummission <= :au                -- période : fin
  AND EXISTS (SELECT 1 FROM demandes_localites dl     -- localité
              WHERE dl.id_demande = demandes.id_demande AND dl.id_localite = :localite)
  AND EXISTS (SELECT 1 FROM demandes_localites dl     -- département (via la localité)
              JOIN localites l ON l.id_localite = dl.id_localite
              WHERE dl.id_demande = demandes.id_demande AND l.id_departement = :departement)
  AND EXISTS (SELECT 1 FROM lignes_demandes ld        -- type d'appui demandé
              WHERE ld.id_demande = demandes.id_demande AND ld.id_type_appui = :type_appui)
```

On utilise `EXISTS` plutôt qu'une jointure : une demande qui couvre trois localités du même département n'est
comptée **qu'une fois**. Un filtre vide est simplement ignoré, et les filtres se cumulent (ET).

Exemples de requêtes construites sur cette base :

```sql
-- Montant engagé et montant dû (attributions non annulées)
SELECT SUM(la.quantite_attribuee * COALESCE(la.cout_attribution, 0) * (1 - COALESCE(la.remise, 0) / 100.0)) AS engage,
       SUM(CASE WHEN a.date_publication IS NOT NULL
                THEN la.quantite_attribuee * COALESCE(la.cout_attribution, 0) * (1 - COALESCE(la.remise, 0) / 100.0)
                ELSE 0 END) AS du
FROM demandes
JOIN attributions a ON a.id_demande = demandes.id_demande AND a.deleted_at IS NULL
JOIN lignes_attributions la ON la.id_attribution = a.id_attribution
WHERE … (population + filtres)

-- Superficie concernée par écologie
SELECT sp.id_type_systeme_production, SUM(sp.superficie)
FROM demandes JOIN systemes_productions sp ON sp.id_demande = demandes.id_demande
WHERE … GROUP BY sp.id_type_systeme_production
```

> Remarque : on écrit `/ 100.0` et pas `/ 100`. Avec `/ 100`, certaines bases (SQLite) font une division entière
> et une remise de 50 % devient 0 %. Un test vérifie ce cas.

**Des vues SQL ont-elles été créées ? Non.** Une vue SQL est une requête enregistrée dans la base, qu'on interroge
comme une table. Il n'y en a pas, pour quatre raisons :

- les chiffres sont toujours à jour, puisqu'ils sont recalculés à chaque affichage ;
- le code reste portable entre SQLite (tests) et PostgreSQL (production) ;
- tout le calcul est lisible au même endroit, dans `StatistiqueCampagneService` ;
- la consigne était de ne pas créer de migration (et une vue se crée par migration).

Une vue, ou une **vue matérialisée** (résultat stocké, rafraîchi la nuit), deviendra utile quand le volume rendra
les pages lentes, ou pour alimenter un outil de BI (§ 2.4).

### 2.3 (Q8 et Q9) Population statistique et sens de « attribuée »

**Oui, les statistiques reposent sur une population : les demandes soumises d'une campagne.** Chaque indicateur se
calcule **pour une campagne** (la campagne en cours par défaut), éventuellement restreinte par les filtres. La
comparaison entre campagnes calcule les mêmes indicateurs, de la même façon, pour chaque campagne, puis les met côte
à côte.

Précisions :

- **Les brouillons** ne font pas partie de la population. Seul le graphique « Demandes par statut » les montre, pour info.
- **Les rendements** sont rattachés à la campagne de la demande, pas à la date de saisie du retour.
- **Les attributions annulées** (suppression douce) sont exclues de tous les calculs.

**« Attribuée » compte dès l'attribution, et c'est voulu.** Attribuer, c'est la décision de la commission. Elle
compte dès qu'elle est enregistrée, même si les résultats ne sont pas encore publiés et même si rien n'est encore
sorti du magasin. Les étapes suivantes ont leurs propres indicateurs :

| Étape | Ce qu'elle mesure | Indicateur |
|---|---|---|
| Attribution | La décision | « Attribuées », taux d'attribution |
| Publication | Rendre la décision officielle, ce qui la rend due | « Montant dû » |
| Retrait (enlèvement) | La sortie physique du magasin | « Délai arbitrage → retrait », bons à retirer |
| Règlement | L'argent encaissé | « Encaissé », taux de recouvrement |

Le statut d'une demande n'est jamais stocké. Il se déduit dans cet ordre : *a une attribution active* → Reçu
attribution ; sinon *a un motif de rejet* → Rejetée ; sinon *pas de date de soumission* → Brouillon ; sinon →
Soumise.

### 2.4 (Q10 et premier point) Statistiques de gestion ou analyse BI : où passe la frontière ?

| | Statistiques de gestion (ORYZA) | Analyse BI |
|---|---|---|
| Question | « Où en est-on ? Qu'est-ce qui bloque ? » | « Pourquoi ? Que va-t-il se passer ? » |
| Données | La base de l'application, en direct | Un entrepôt alimenté par plusieurs sources |
| Calculs | Prévus à l'avance par le développeur | Explorés librement par l'analyste |
| Temps | La campagne en cours, quelques campagnes | Des séries longues, des tendances |

**La comparaison entre campagnes, telle qu'ORYZA la fait, reste de la statistique de gestion.** Elle compare des
indicateurs que l'application calcule déjà, de la même façon pour chaque campagne. Elle sert au pilotage (« faisons-
nous mieux que la campagne passée ? »).

**On entre dans la BI quand on :**

- croise ORYZA avec des données extérieures (pluviométrie, prix du marché, données d'autres organismes) ;
- explore librement (« et si je regarde par sexe, par âge, par coopérative… ») sans attendre un nouvel écran ;
- cherche des causes (« les attributaires d'engrais produisent-ils plus ? ») ou des prévisions (budget de la
  campagne prochaine) ;
- travaille sur des années d'historique, dans un entrepôt séparé, pour ne pas ralentir l'application.

La v2 prépare ce passage : les campagnes clôturées sont figées, les valeurs de référence sont historisées, et les
exports Excel ainsi que le rapport PDF sont tracés.

### 2.5 (Q11) Objectifs cibles : pourquoi ORYZA n'en a pas

Un **objectif cible** est une valeur qu'une organisation se fixe avant une période (« attribuer 70 % des
demandes ») pour juger ensuite son résultat. C'est utile quand l'organisation a des engagements chiffrés,
envers un bailleur par exemple.

**L'ADERIZ n'a pas d'objectifs chiffrés : elle appuie les demandeurs selon les demandes et les stocks.** Les
objectifs ont donc été retirés de l'application. Les statistiques **constatent** (combien de demandes, combien
servies, en combien de temps) et se comparent **à la campagne précédente**, sans cible à atteindre.

Restent les **seuils d'alerte opérationnels**, qui ne jugent pas un résultat mais déclenchent une action :
- le seuil d'alerte de stock de chaque appui (réglé dans le catalogue), qui prévient quand il faut réapprovisionner ;
- les délais des rappels du lundi (réglés dans les paramètres généraux), qui relancent les bons non retirés,
  les brouillons oubliés et les séances restées ouvertes.

### 2.6 (Q13) Une application de gestion peut-elle produire des rapports ?

Oui. C'est même une de ses fonctions de base : restituer l'activité sous une forme qu'on transmet. ORYZA produit :

- **des documents de travail** : bons d'attribution, courriers, fiches d'arbitrage, impression d'une demande et de
  ses pièces ;
- **des exports de données** : demandes, attributions, stocks (Excel) ;
- 🆕 **le rapport de campagne (PDF)** : chiffres clés, finances, superficies et rendements, satisfaction
  par appui, répartition. Il respecte les filtres choisis et indique s'il est **provisoire** (campagne ouverte) ou
  **définitif** (campagne clôturée). On le télécharge depuis Statistiques ou Pilotage, avec le droit « Télécharger
  les rapports ».

Chaque export et chaque rapport téléchargé est noté au journal. Prochaine étape possible : l'envoi automatique du
rapport à la direction chaque mois (planificateur + e-mail).

### 2.7 (Q12) L'historisation optimisée

| Besoin | Solution v2 | Où |
|---|---|---|
| Savoir **ce qui a changé** | Valeurs avant → après au journal pour les demandes, attributions, appuis (prix, dose, seuil), comptes, référentiels, paramètres, droits, corrections de rendement | `Ecarts`, services concernés |
| Ne plus **perdre** une attribution annulée | Suppression douce (`deleted_at`) : invisible dans les écrans et les calculs, conservée en base avec ses lignes, plus une copie au journal | `Attribution` (SoftDeletes) |
| Savoir **qui se connecte** | Connexions, échecs (adresse saisie, motif) et déconnexions, avec l'adresse IP | `JournaliserConnexions` |
| Savoir **qui sort des données** | Chaque export et rapport téléchargé, avec ses critères | `JournaliserExport` |
| Retracer **l'histoire d'un dossier** | Chronologie sur la fiche de la demande, y compris les attributions annulées | `ChronologieDemandeService` |
| Garder les **comptes** et leur historique | Désactivation au lieu de suppression, y compris « Fermer mon compte » | `CompteService`, `ProfileController` |
| Figer une **campagne terminée** | Clôture : plus aucune modification, chiffres définitifs | `ClotureCampagneService` |
| **Retrouver** une action | Filtre du journal par action, auteur et période | Administration → Journal |

Le journal ne se modifie ni ne se supprime : on y ajoute seulement des lignes. C'est un **journal d'audit**.

Les données ne sont pas « temporaires » : rien ne les efface automatiquement. Les **copies de sauvegarde** non
plus : il n'y a pas de nettoyage automatique (voir § 2.9). Reste à faire (⏳) : une politique écrite de conservation
et d'anonymisation des données personnelles des demandeurs.

### 2.8 (Q14) Le mode hors-ligne

Le travail de terrain se fait souvent sans réseau. La v2 ajoute :

1. **un bandeau** « Vous êtes hors ligne » dès que la connexion tombe ;
2. **le service worker** (`public/sw.js`) : les fichiers de l'application (styles, scripts, images) sont gardés en
   cache, et les pages de terrain déjà ouvertes (tableau de bord, demandes, nouvelle demande, saisie en série,
   attributions) restent consultables. Une page jamais ouverte affiche `hors-ligne.html` ;
3. **le brouillon local** : si le serveur ne répond pas, la saisie d'une demande est gardée sur l'appareil. Au retour
   du réseau, elle est envoyée au serveur comme brouillon, et l'envoi définitif du formulaire est bloqué tant qu'on
   est hors ligne (les pièces jointes et les contrôles exigent le serveur) ;
4. **à la déconnexion**, les pages et brouillons gardés sont effacés : un appareil partagé ne garde rien de la
   session précédente.

Limite assumée : on ne **valide** pas une demande hors ligne. Les règles (acteur en impayé, campagne clôturée,
visite obligatoire) ne se vérifient qu'au serveur.

### 2.9 Le nettoyage des sauvegardes (retiré)

Le « nettoyage » était la commande `backup:clean`, lancée chaque nuit à 2 h 30. Elle **supprimait les anciennes
copies de sauvegarde** pour ne garder qu'un échantillon : toutes celles des 7 derniers jours, puis une par jour
pendant 16 jours, une par semaine pendant 8 semaines, une par mois pendant 4 mois et une par an pendant 2 ans. Elle
ne touchait jamais aux données de l'application, seulement aux archives `.zip`.

**Il est retiré : plus aucune archive n'est supprimée automatiquement.** Conséquence à surveiller : les archives
s'accumulent (une par nuit). L'écran « Santé du système » affiche l'espace disque libre, et `backup:monitor` (3 h)
prévient l'administrateur quand les archives dépassent 5 Go. Il faut alors supprimer les plus anciennes à la main, ou
remettre le nettoyage en planifiant de nouveau `backup:clean` dans `routes/console.php`.

### 2.10 Des campagnes « obsolètes » après chaque année : est-ce logique ?

**Qu'une campagne se termine, oui. Qu'elle devienne obsolète au sens « inutile ou à effacer », non.**

- Une campagne correspond à un **cycle de culture** (ORYZA en connaît plusieurs : Cycle 1, 2, 3…), avec une date de
  début et une date de fin. Il est logique qu'on ne dépose plus de demande sur un cycle terminé.
- Mais ses données gardent leur valeur après la fin :
  - **les règlements** : un acteur peut encore devoir de l'argent sur une campagne passée, et il reste bloqué pour
    toute nouvelle demande tant qu'il n'a pas soldé ;
  - **les rendements** arrivent après la récolte, donc souvent après la fin du cycle ;
  - **la comparaison** et l'historique d'un demandeur (ce qu'il a déjà reçu) ;
  - **les contrôles** (audit, bailleurs) qui portent sur les années passées.

La bonne pratique est donc de **clôturer** la campagne (elle devient en lecture seule) plutôt que de la considérer
comme jetable. C'est ce que fait la v2 : une campagne clôturée refuse demandes, attributions et rendements, accepte
encore les règlements, et reste consultable dans les statistiques. Une campagne n'est jamais supprimée tant qu'elle
porte des demandes.

Deux améliorations possibles (⏳) :
1. **proposer automatiquement la clôture**, par exemple un mois après la date de fin, sur décision de
   l'administrateur ;
2. **attendre les rendements** avant la clôture définitive, ou les autoriser après clôture si la récolte est tardive.

### 2.11 En quoi consistent les « paramètres généraux » d'une application ?

Ce sont les **réglages de fonctionnement que l'administrateur peut changer lui-même, sans développeur et sans
redéployer l'application**. Ils ne contiennent pas de données métier (demandes, stocks) : ils fixent **comment**
l'application se comporte.

Exemples courants dans les applications de gestion : politique de mot de passe, durée de session, nombre de
tentatives de connexion, délais de relance, coordonnées de l'organisation sur les documents, devise et format des
nombres, adresse d'envoi des e-mails, seuils d'alerte par défaut.

Dans ORYZA (Administration → Système → Paramètres généraux) :

| Groupe | Paramètre | Valeur par défaut |
|---|---|---|
| Mots de passe | Longueur minimale | 8 |
| | Exiger majuscules et minuscules | oui |
| | Exiger un chiffre | oui |
| | Exiger un symbole | non |
| Connexion | Tentatives avant blocage temporaire | 5 |
| Rappels automatiques | Bon non retiré, brouillon oublié, séance ouverte (jours) | 14, 7, 30 |

Chaque valeur a des bornes (on ne peut pas mettre un mot de passe de 2 caractères) et chaque modification est notée
au journal avec l'ancienne et la nouvelle valeur. La règle à retenir : ce qui change souvent et relève d'une décision
de l'organisation va dans les paramètres ; ce qui touche à la logique métier reste dans le code.

### 2.12 Tableau de bord ou tableau de pilotage : quelle différence ?

| | Tableau de bord | Tableau de pilotage |
|---|---|---|
| Pour qui | Chaque agent (DP, commission, magasin, comptable…) | La direction |
| Question | « Qu'ai-je à faire aujourd'hui ? » | « Comment se passe la campagne, où faut-il intervenir ? » |
| Horizon | Le jour, la semaine : le travail en cours | La campagne entière, comparée à la précédente |
| Contenu | Ses propres dossiers, ses tâches en attente (à arbitrer, bons à servir, stocks bas, impayés) | Les grands indicateurs (volumes, taux, délais, montants), leur évolution, les points de blocage |
| Usage | Agir : chaque chiffre mène à un dossier à traiter | Décider : réorienter, relancer un service, alerter |
| Dans ORYZA | Menu « Tableau de bord », différent selon le rôle | Menu « Pilotage » (rôle Directeur, lecture seule) |

En résumé, le tableau de bord sert le **travail quotidien** (opérationnel), et le tableau de pilotage sert la
**conduite de l'activité** (stratégique). Les deux lisent les mêmes données, mais à des niveaux différents.

---

## 3. Administration : ce que la v2 donne à l'administrateur

| Écran (Administration) | Usage |
|---|---|
| Accès → **Utilisateurs** | Un seul rôle par compte, dernière connexion, **Désactiver / Réactiver** (avec motif). On ne peut désactiver ni soi-même, ni le dernier administrateur actif. Une session ouverte est fermée aussitôt. |
| Accès → **Rôles et droits** | Cocher ou décocher les droits de chaque rôle. Le rôle administrateur n'est pas modifiable, et chaque changement est journalisé (droits ajoutés et retirés). |
| Système → **Paramètres généraux** | Longueur et complexité du mot de passe, tentatives de connexion avant blocage, délais des rappels. Les valeurs sont bornées et chaque changement est journalisé. |
| Référentiels → **Campagnes** | Dates, **Clôturer** (avec le nombre de dossiers encore en cours), **Rouvrir** (motif obligatoire). |
| Système → **Santé du système** | Base de données, sauvegardes, tâches automatiques, file d'attente, disque, mode débogage, connexions refusées, comptes. |
| Système → Journal, Sauvegardes | Déjà présents ; le journal affiche maintenant les écarts avant → après et l'adresse IP. |

Le seeder des rôles (`RolePermissionSeeder`), relancé à chaque déploiement, **ne défait plus** les réglages de
l'administrateur. Il crée les rôles nouveaux avec leurs droits par défaut (`RoleEnum::droitsParDefaut()`), donne les
droits nouveaux aux rôles concernés, et laisse le reste intact.

---

## 4. Points d'attention pour la mise en production

1. **Le schéma a été modifié dans les migrations existantes**, comme demandé (aucune nouvelle migration). Chaque
   ajout est dans un bloc « Schéma v2 », séparé de la définition d'origine de la table :
   - `0001_01_01_000000_create_users_table` : `users.actif`, `desactive_le`, `motif_desactivation`,
     `derniere_connexion_le`, et la table `parametres` ;
   - `…_create_campagnes_table` : clôture (`cloturee_le`, `id_user_cloture`) ;
   - `…_create_attributions_table` : `deleted_at` (suppression douce) ;
   - `…_create_journals_actions_table` : `user_id` facultatif, `adresse_ip`, index.

   ⚠️ Une base **déjà installée** a enregistré ces migrations comme exécutées : `php artisan migrate` ne lui
   ajoutera pas ces colonnes, et l'application échouera (connexion comprise). Il faut **reconstruire la base**
   (`php artisan migrate:fresh --seed`, ce qui efface les données), ou appliquer ces blocs à la main. Si les données
   de production doivent être gardées, il vaut mieux revenir à des migrations d'ajout séparées.
2. **Mots de passe** : la nouvelle politique (8 caractères, majuscules et minuscules, un chiffre) ne vaut que pour
   les mots de passe saisis à partir de maintenant. Les comptes de démonstration gardent « password ».
3. **Comptes de démonstration** ajoutés : `directeur@example.com` et `comptable@example.com`.
4. **Le planificateur** doit tourner (cron ou appel externe de `/planification/executer`) pour que l'écran de santé
   voie son passage.
