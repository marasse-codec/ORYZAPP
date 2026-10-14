# Architecture par contextes

Le code métier est rangé par **contexte** (un sujet métier, ses règles et ses
données) sous `app/Domain/<Contexte>`. Les couches HTTP (`app/Http`), l'interface
d'administration (`app/Filament`) et les vues (`resources/views`) ne contiennent
aucune règle métier : elles appellent les cas d'usage des contextes.

## Les contextes

| Contexte | Rôle | Ce qu'il porte |
|---|---|---|
| **Demande** | Recevoir et instruire une demande d'appui | `Demande`, `LigneDemande`, `Acteur`, `PieceDemande`, `Traitement` ; création (unitaire et en série), modification, visite de terrain, soumission |
| **Commission** | Arbitrer et attribuer | `SessionCommission`, `Attribution`, `LigneAttribution` ; séances (ouverture, clôture, publication), attributions, synthèse d'arbitrage, simulation de sortie |
| **Stock** | Tenir le magasin | `Appui`, `Equipement`, `Approvisionnement`, `MouvementStock`, `Agent` ; catalogue, entrées, retraits sur bon, restitutions |
| **Referentiel** | Les listes de référence | `Campagne`, `Departement`, `Localite`, `Service`, `TypeActeur`, `TypeAppui`, `TypePiece`, `TypeSystemeProduction`, `UniteDosage` |
| **Acces** | Comptes, rôles, permissions | `RoleEnum`, `PermissionEnum`, création des comptes |
| **Notification** | Alerter aux bons moments | alertes de stock, résumé quotidien, moteur de règles |
| **Journal** | Garder la trace des actions | `JournalAction` (qui, quoi, sur quoi, quand) |
| **Suivi** | Statistiques et exports | indicateurs de campagne, export Excel, assistant en langage naturel |
| **Shared** | Ce que tous partagent | `RegleMetierViolee`, `EstUtilisable` |

Chaque contexte se compose, selon ses besoins, de `Models`, `Enums`, `Services`,
`Observers`, `Policies`, `Notifications`, `Exports`.

## Principes

**Un cas d'usage = un service applicatif.** `AttributionService::enregistrer()`,
`RetraitService::servir()`, `RestitutionService::restituer()`… Le contrôleur
valide la requête (FormRequest), appelle le service et choisit la réponse. Le
service porte la règle.

**ACID.** Un cas d'usage est **une seule transaction** :
- les lignes concernées sont verrouillées (`lockForUpdate`) avant d'être lues pour décider
  (stock, séance, équipement) : deux opérations simultanées ne se croisent pas ;
- la trace au journal est écrite **dans** la transaction : pas d'action sans trace, pas de trace sans action ;
- les notifications partent **après** le commit : on n'alerte pas d'un état qui pourrait être annulé.

**Règle violée = exception de domaine.** Un service lève `RegleMetierViolee` ; la
couche HTTP (`bootstrap/app.php`) la transforme en message sur la page d'où l'on
vient (ou en 409 pour une requête JSON). Les contrôleurs n'ont donc pas à
reproduire les `try/catch`.

**Les états se déduisent, ils ne se stockent pas.** Le statut d'une demande et
celui d'une attribution découlent de dates (soumission, clôture de séance,
publication, retrait). Deux sources de vérité finiraient par se contredire. Pour
compter en SQL, `Demande::statutSql()` et `Attribution::statutSql()` expriment la
même déduction, et un test garantit qu'elles restent identiques.

**Le stock ne bouge que par des mouvements.** `quantite_en_stock` varie
uniquement par un approvisionnement (+), un retrait sur bon (−) ou une
restitution de matériel (+), et chaque variation laisse un `MouvementStock`
signé d'un agent et rattaché à son origine (approvisionnement ou attribution).
Le catalogue des appuis ne modifie jamais la quantité.

**Suppression sûre des référentiels.** Un élément déjà utilisé (`EstUtilisable`)
ne se supprime pas : l'historique des dossiers s'y rattache.

## Interfaces

- **Pages Blade** (`resources/views`, composants `components/ui/*`) : tout le travail
  quotidien — demandes, commission, magasin (appuis, approvisionnements, enlèvements,
  équipements, mouvements), attributions, statistiques, éditions. Charte verte,
  menu en tiroir sur téléphone, tableaux qui se replient en cartes.
- **Administration Filament** (`/administration`, permission `gerer-ressources`,
  administrateur seulement) : référentiels, comptes et rôles, règles de
  notification, journal des actions. Rien d'autre.

## Où ajouter quoi

| Besoin | Où |
|---|---|
| Une nouvelle règle sur les attributions | `Domain/Commission/Services` |
| Un nouvel écran | contrôleur dans `Http/Controllers/<Contexte>`, vue dans `resources/views/<sujet>`, route sous le middleware de permission voulu |
| Une nouvelle liste de référence | modèle dans `Domain/Referentiel/Models` + une ressource Filament (`ReferentielResource`) |
| Une action à journaliser | un cas dans `ActionJournalEnum`, et `JournalAction::enregistrer()` dans la transaction du service |
