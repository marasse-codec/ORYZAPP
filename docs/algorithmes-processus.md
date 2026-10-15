# Le processus d'une demande, de bout en bout, et les algorithmes utilisés

Ce document suit une demande d'appui de sa saisie jusqu'à la fin de la campagne. À chaque étape, il dit **ce que
fait l'application**, **où** (classe principale) et **le nom connu de l'algorithme ou de la technique** employée,
pour qu'un développeur qui reprend le code sache tout de suite de quoi il s'agit.

Vue d'ensemble :

```
Saisie → Visite → Soumission → Instruction → Arbitrage (séance) → Publication
      → Retrait au magasin → Règlement → Rendement → Statistiques / Pilotage → Clôture de la campagne
```

---

## 0. Avant tout : se connecter

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Le mot de passe est comparé à son empreinte, jamais stocké en clair | `LoginRequest`, Laravel | **Hachage bcrypt** (fonction de hachage lente avec sel) |
| Après N échecs (paramètre, 5 par défaut), la connexion est bloquée un moment pour ce couple e-mail + adresse IP | `LoginRequest::ensureIsNotRateLimited` | **Limitation de débit à fenêtre fixe** (*fixed window rate limiting*) |
| Un compte désactivé est refusé, même avec le bon mot de passe ; une session ouverte est fermée à la page suivante | `LoginRequest`, middleware `CompteActif` | **Contrôle d'accès** à chaque requête (*filtre / intercepteur*) |
| Le mot de passe doit respecter la politique réglée par l'administrateur | `ParametreService::reglePassword` | **Validation par règles** |
| Chaque page n'est accessible qu'avec le bon droit | `permission:` sur les routes, `Gate::before` pour l'admin | **RBAC** (*Role-Based Access Control*, contrôle d'accès par rôle) |
| Chaque formulaire porte un jeton secret | Laravel | **Jeton anti-CSRF** (*synchronizer token*) |
| Connexions, échecs et déconnexions sont notés | `JournaliserConnexions` | **Patron Observateur** (écoute d'événements) + **journal d'audit** |

## 1. Saisie de la demande

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Le demandeur est retrouvé par nom + date de naissance exacts, sinon créé | `CreationDemandeService::resoudreDemandeur` | **Recherche par clé naturelle** (correspondance exacte), évite les doublons |
| Un acteur qui doit encore de l'argent ne peut pas redemander | `ReglementService::verifierActeurLibre` | **Règle de gestion bloquante** (contrôle préalable) |
| Une campagne clôturée refuse la demande | `Campagne::exigerOuverte` | **Garde** (*guard clause*) |
| Demandeur, demande, localités, superficies, lignes et pièces sont enregistrés ensemble ou pas du tout | `DB::transaction` | **Transaction ACID** (tout ou rien) |
| Les services à prévenir se déduisent des appuis demandés, sans doublon | `Traitement::creerTraitements`, `Demande::servicesConcernes` | **Dédoublonnage par clé** (`unique`, `firstOrCreate`) |
| La saisie est sauvegardée automatiquement 1,5 s après la dernière frappe | `demandes/create.blade.php` | **Anti-rebond** (*debounce*) |
| 🆕 Sans réseau, la saisie est gardée dans le navigateur et renvoyée au retour de la connexion | `hors-ligne.js`, `sw.js` | **Stockage local + synchronisation différée** (*store and forward*) |
| 🆕 Les pages déjà ouvertes restent consultables hors ligne | `public/sw.js` | **Cache d'abord** pour les fichiers statiques (*cache-first*), **réseau d'abord** pour les pages (*network-first*) |
| Saisie en série : plusieurs acteurs, une seule transaction ; si un acteur est refusé, tout le lot est annulé | `CreationDemandeSerieService` | **Traitement par lot atomique** |

## 2. Visite et soumission

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Pas de soumission sans visite de terrain enregistrée | `SoumissionDemandeService` | **Pré-condition** (règle de workflow) |
| La date de soumission fait passer la demande de « Brouillon » à « Soumise » | `Demande::soumettre` | **Machine à états** (états déduits, voir § 9) |

## 3. Instruction par les services

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Chaque agent ne voit que les demandes qui touchent son service | `Demande::scopePourUtilisateur` | **Filtrage par périmètre** (sécurité au niveau des lignes, *row-level filtering*) |
| Résumé quotidien à 7 h des nouvelles demandes du service | `AlerteService::resumeQuotidien` | **Agrégation planifiée** (traitement par lot) |

## 4. Arbitrage en séance de commission

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Le tableau de confrontation met en tête les appuis les plus en tension (demandé contre disponible) | `SyntheseCommissionService::confrontation` | **Tri décroissant par écart** (priorisation) |
| Disponible = stock − quantités déjà engagées sur des bons non retirés | `SyntheseCommissionService::quantitesEngagees` | **Agrégat SQL `SUM … GROUP BY`** |
| Simulation des sorties, chaque utilisateur ayant son brouillon | `SimulationSortieService` | **Cache avec durée de vie** (TTL) + **verrou exclusif** (*mutex*) contre les écritures croisées |
| Deux agents ne peuvent pas attribuer la même demande en même temps, ni pendant la clôture de la séance | `AttributionService` (`lockForUpdate`) | **Verrouillage pessimiste** (`SELECT … FOR UPDATE`) |
| 🆕 Annuler une attribution la marque sans l'effacer | `Attribution` (SoftDeletes) | **Suppression logique** (*soft delete*) |
| 🆕 Le journal garde ce qui a changé dans une attribution modifiée | `Ecarts::entre` | **Calcul de différence entre deux états** (*diff* de dictionnaires) |

## 5. Publication des résultats

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| La clôture de la séance vaut validation ; la publication date chaque attribution et prévient les intéressés | `SessionCommission`, `AlerteService::resultatsPublies` | **Patron Observateur** + **notifications multi-canal** (base, e-mail, push) |

## 6. Retrait au magasin (enlèvement)

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Le bon et chaque appui sont relus sous verrou, puis le stock est diminué | `EnlevementService` | **Verrouillage pessimiste** + **décrément atomique** (`decrement`) |
| Chaque sortie ou entrée est une ligne de mouvement : le stock se retrouve en rejouant les mouvements | `MouvementStock` | **Registre de mouvements** (principe de la **comptabilité en partie simple**, journal de stock) |
| Une alerte part quand le stock **passe** sous le seuil (pas à chaque sortie quand il y est déjà) | `AlerteService::franchitLeSeuil` | **Détection de franchissement de seuil** (*edge detection* : avant > seuil et après ≤ seuil) |
| Un équipement est suivi à l'unité (matricule, en service ou non) | `Equipement`, `RestitutionService` | **Suivi unitaire** (inventaire par numéro de série) |

## 7. Règlement (comptable)

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Montant d'une ligne = quantité × coût × (1 − remise %) ; montant de l'attribution = somme des lignes | `LigneAttribution::montant` | **Calcul de facture** (somme de produits) |
| Reste dû = montant − paiements, jamais négatif ; rien n'est dû avant publication | `Attribution::resteDu` | **Solde courant** (déduit, jamais stocké) |
| Deux encaissements simultanés ne peuvent pas dépasser le dû | `ReglementService::enregistrerPaiement` | **Verrouillage pessimiste** |
| « Acteur en impayé » n'est pas une case cochée : il se déduit des soldes | `ReglementService::acteursEnImpaye` | **État dérivé** + **regroupement** (`groupBy`) et **tri décroissant** |

## 8. Rendement

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Rendement d'une écologie = production totale ÷ superficie récoltée totale | `StatistiqueCampagneService::rendementsParEcologie` | **Moyenne pondérée** (une grande parcelle pèse plus qu'une petite) |
| 🆕 Une correction garde l'ancienne valeur au journal | `RendementService` | **Historisation avant/après** |

## 9. Statistiques, pilotage et rapports

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Le statut d'une demande se calcule dans l'ordre : attribution, sinon rejet, sinon brouillon, sinon soumise | `Demande::statutSql` / `getStatutAttribute` | **Table de décision à priorité** (`CASE WHEN`), même règle en SQL et en PHP |
| Comptages, sommes et sous-totaux faits par la base | `StatistiqueCampagneService` | **Agrégation SQL** (`COUNT`, `SUM`, `GROUP BY`, `COUNT(DISTINCT)`) |
| 🆕 Filtres par localité, département et type d'appui sans compter deux fois une demande | `FiltreStatistique::appliquer` | **Semi-jointure** (`WHERE EXISTS`) |
| Délais moyens en jours | `delaiMoyen` | **Moyenne arithmétique** |
| Les 10 localités les plus demandeuses | `parLocalite` | **Top-k** (tri + `LIMIT`) |
| Taux d'attribution, de traitement, de satisfaction, de recouvrement | services Suivi | **Ratio** (partie ÷ total × 100), « - » si le total est nul |
| 🆕 Évolution d'une campagne à la suivante | `ComparaisonCampagnesService::evolution` | **Taux de variation** ((après − avant) ÷ \|avant\| × 100) |
| 🆕 Le directeur voit la campagne et ce qui bloque | `PilotageService` | **Tableau de bord** (*indicateurs clés de performance*) |
| 🆕 Rapport de campagne | `StatistiqueController::rapport`, dompdf | **Génération de document** (gabarit HTML → PDF) |

## 10. Fin de la campagne

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| 🆕 Clôture : plus de demande, d'attribution ni de rendement ; les règlements continuent | `ClotureCampagneService`, `Campagne::exigerOuverte` | **Gel des données** (période comptable clôturée) + **verrouillage pessimiste** |
| 🆕 Réouverture exceptionnelle, motif obligatoire et tracé | `ClotureCampagneService::rouvrir` | **Journal d'audit** |

## 11. En continu : traçabilité et exploitation

| Ce qui se passe | Où | Algorithme / technique |
|---|---|---|
| Toute action importante est ajoutée au journal, jamais modifiée ni supprimée | `JournalAction` | **Journal en ajout seul** (*append-only audit log*) |
| Le journal pointe vers n'importe quel objet (demande, attribution, campagne…) | `cible_type` / `cible_id` | **Association polymorphe** |
| 🆕 Chronologie d'un dossier reconstituée à partir du journal | `ChronologieDemandeService` | **Reconstitution d'historique par événements** (inspiré de l'*event sourcing*) |
| 🆕 Chaque export téléchargé est tracé | `JournaliserExport` | **Intercepteur** (middleware) |
| Sauvegarde chaque nuit de la base et des pièces, toutes les archives conservées (pas de nettoyage automatique) | spatie/laravel-backup | **Sauvegarde complète planifiée** (*full backup*) + **surveillance de l'âge et de la taille** |
| 🆕 Le planificateur note son passage chaque minute ; l'écran de santé vérifie qu'il est récent | `SanteSystemeService` | **Signal de vie** (*heartbeat*) |
| 🆕 Les paramètres sont lus une fois par requête | `ParametreService` | **Mémoïsation** (cache le temps de la requête) |
| 🆕 Les rôles se mettent à jour à chaque déploiement sans écraser les réglages de l'administrateur | `RolePermissionSeeder` | **Migration de données idempotente** (rejouable sans effet de bord) |

---

### Les patrons de conception (design patterns) qu'on retrouve dans le code

| Patron | Exemple dans ORYZA |
|---|---|
| **Service** (couche métier) | `CreationDemandeService`, `ReglementService`, `ClotureCampagneService`… : une classe par cas d'usage, qui tient la règle et la transaction |
| **Objet valeur** (*value object*) | `FiltreStatistique`, `Verification` : de petits objets immuables qui portent une donnée et ses calculs |
| **Observateur** | `AppuiObserver`, `JournaliserConnexions` |
| **Énumération riche** | `RoleEnum`, `PermissionEnum`, `ParametreEnum`, `ActionJournalEnum` : chaque valeur porte son libellé, ses défauts et ses règles |
| **Contrôleur mince** | les contrôleurs valident l'entrée (FormRequest), appellent un service et renvoient une vue |
| **Domaines séparés** (DDD léger) | `app/Domain/{Demande, Commission, Stock, Suivi, Journal, Acces, Administration, Referentiel}` |
