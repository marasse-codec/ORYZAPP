# Diagrammes de la v2

Sources PlantUML (`.puml`) et rendu (`.svg`) des diagrammes qui décrivent les évolutions de gestion.
Les éléments ajoutés par la v2 sont **en vert**. Les explications sont dans
[`../roadmap-gestion.md`](../roadmap-gestion.md) et [`../algorithmes-processus.md`](../algorithmes-processus.md).

Pour régénérer les SVG : `java -jar plantuml.jar -tsvg docs/v2/**/*.puml` (Graphviz requis).

## Cas d'utilisation, un par acteur (`usecases/`)

| Fichier | Acteur |
|---|---|
| `uc_administrateur` | Administrateur : comptes, droits, paramètres, clôture, santé |
| `uc_directeur` | Directeur (nouveau) : pilotage, statistiques filtrées, comparaison, rapport |
| `uc_comptable` | Comptable (nouveau) : caisse, règlements, impayés |
| `uc_delegue_pole` | Délégué de pôle : demandes (y compris hors ligne), visite, rendements |
| `uc_agent_service` | Agent de service |
| `uc_agent_commission` | Agent de commission |
| `uc_gestionnaire_stock` | Gestionnaire de stock |
| `uc_visiteur` | Visiteur (acteur de la filière) : consulte la vitrine, sans compte ni droit |
| `uc_planificateur` | Planificateur (acteur système) |

## Diagrammes de communication (`communication/`)

Les objets sont reliés par des liens ; les messages sont numérotés dans leur ordre d'envoi (1, 1.1, 1.1.1…).

| Fichier | Scénario |
|---|---|
| `com_cloturer_campagne` | L'administrateur clôture une campagne |
| `com_enregistrer_reglement` | Le comptable enregistre un règlement |
| `com_consulter_pilotage` | Le directeur consulte le tableau de pilotage |
| `com_statistiques_filtrees` | Statistiques d'une campagne avec filtres |
| `com_desactiver_compte` | Désactiver un compte, puis tentative de connexion refusée |
| `com_modifier_droits` | Modifier les droits d'un rôle |
| `com_saisie_hors_ligne` | Saisir une demande sans réseau, puis synchroniser |

## Composants et classes

| Fichier | Contenu |
|---|---|
| `composants/composants_v2` | Les couches (navigateur, contrôle, domaine, infrastructure) et les composants ajoutés |
| `classes/classes_v2` | Les entités modifiées (User, Campagne, Attribution, JournalAction) et les nouvelles (Parametre, FiltreStatistique) |
