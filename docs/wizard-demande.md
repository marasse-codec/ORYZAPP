# Wizard de demande d'appui — guide technique

Fichiers concernés :

- `resources/views/demandes/create.blade.php`
- `app/Http/Controllers/DemandeController.php`

---

## 1. Le parcours

Le formulaire pose deux questions distinctes, dans cet ordre :

| Étape | Contenu |
|---|---|
| 1. Bénéficiaires & parcelles | Pour chaque bénéficiaire : téléphone, nom, e-mail, localité, puis une superficie par type de culture. Nombre de bénéficiaires libre. |
| 2. Type d'acteur & documents | Producteur individuel, groupement informel, coopérative ou entreprise/ONG. Ce choix conditionne les pièces à fournir — pas la suite du parcours. |
| 3. Appuis sollicités | Le catalogue des types d'appui, en cases à cocher. Cocher un type révèle un champ de quantité/précisions (obligatoire) et la liste des appuis précis de ce type (facultative). Au moins un type est requis. |

Chaque bénéficiaire est un panneau dépliable. En ajouter un replie les précédents, pour qu'une coopérative de trente membres reste lisible.

---

## 2. Le principe central : le nom du champ porte la structure

C'est l'idée dont tout le reste découle. PHP décode les crochets d'un attribut `name` et reconstruit un tableau imbriqué. Le formulaire n'a donc rien à sérialiser : il suffit de nommer correctement.

```blade
{{-- Dans la vue, un name construit dynamiquement --}}
:name="`beneficiaires[${bIdx}][superficies][3]`"
```

```php
// Côté PHP, sans une ligne de parsing
$request['beneficiaires'][0]['superficies'][3]  // => "2.5"
```

Deux conséquences pratiques :

**Alpine ne soumet rien.** Il pilote uniquement l'affichage et la liste des lignes ; c'est le navigateur qui envoie le formulaire, nativement. D'où la règle : tout champ doit conserver un `:name`. Un champ sans `name` est invisible pour le serveur, quelle que soit sa valeur dans `x-model`.

**Les superficies sont un dictionnaire, pas une liste.** La clé *est* l'identifiant du type de culture :

```php
'superficies' => [
    3 => '2.5',   // 3 = id_type_culture (riz irrigué)
    7 => '0.75',  // 7 = id_type_culture (riz pluvial)
]
```

Cela évite de transporter un champ `type_culture_id` et rend impossible la saisie du même type deux fois. La boucle `@forelse` génère un champ par type de culture présent en base : ajouter une culture en base l'ajoute au formulaire, sans toucher au code.

Les appuis de l'étape 3 suivent exactement le même schéma — `appuis[<id_type_appui>]` contient les précisions saisies :

```php
'appuis' => [
    2 => '50 sacs de NPK',
    5 => '1 décortiqueuse 2 t/h',
]
```

La case à cocher, elle, n'a pas de `name` : c'est la **présence de la clé** dans l'objet Alpine qui fait foi. Décocher supprime la clé (`delete this.appuis[id]`), le `<template x-if>` retire l'input du DOM, et le champ disparaît de la requête. Aucun filtrage côté serveur n'est nécessaire : ce qui arrive est exactement ce qui a été coché.

---

## 3. La vue : un état, deux dérivés

Tout l'état tient dans un seul `x-data` à la racine. Deux valeurs seulement sont stockées ; le reste est calculé.

```js
currentStepId: 1,
beneficiaires: [ { telephone, nom, email, localite_id, expandido, superficies } ],

// Dérivés — jamais stockés, donc jamais désynchronisés
get steps()            { /* la liste des étapes */ },
get currentStepIndex() { /* position dans cette liste */ },
```

Utiliser un *getter* plutôt qu'une variable pour `currentStepIndex` supprime une classe entière de bugs : l'index ne peut pas contredire l'étape affichée puisqu'il en est recalculé à chaque lecture. Le fil d'Ariane, le libellé « étape N sur 2 » et l'affichage du bouton « Soumettre » lisent tous la même source.

`expandido` est une préférence d'affichage, pas une donnée métier : il vit dans le même objet par commodité, et le serveur l'ignore faute de `name` correspondant.

---

## 4. Du clic à la base

1. **Le navigateur poste le formulaire** — envoi natif en `multipart/form-data`, nécessaire pour les pièces jointes. Aucun JavaScript n'intervient. (`POST /api/demande` → `demandes.store`)
2. **La fabrique choisit une stratégie** — `DemandeCreationStrategyFactory::create()` délègue à une implémentation de `DemandeCreationStrategy`. Une seule existe aujourd'hui ; l'intention est d'en avoir une par forme juridique.
3. **Validation par motif** — les règles utilisent l'astérisque pour s'appliquer à chaque ligne : `beneficiaires.*.telephone` vaut pour le premier bénéficiaire comme pour le trentième.
4. **Résolution des acteurs, dans une transaction** — chaque téléphone est cherché en base ; s'il est inconnu, un acteur est créé. Le premier de la liste devient le porteur de la demande.
5. **Écriture en cascade** — `demandes` → `beneficiaires` → `cultures` (une par superficie strictement positive) → `pieces_demandes`.

---

## 5. Pourquoi c'est écrit ainsi

**Le téléphone comme identité.** Un producteur en zone rurale n'a ni compte ni adresse e-mail, mais toujours un numéro. D'où la suppression de la liste déroulante d'acteurs : on saisit un numéro, le système retrouve ou crée la fiche. Le déposant n'a pas à savoir si le bénéficiaire existe déjà.

**Tout ou rien.** L'écriture est enveloppée dans une transaction. Une demande à douze bénéficiaires qui échoue au onzième ne laisse pas onze fiches orphelines.

**Les superficies vides sont ignorées.** Le formulaire affiche une case par type de culture, mais seules les valeurs `> 0` produisent une ligne. Un producteur de riz irrigué ne génère pas six lignes vides.

**Stratégie plutôt que conditions.** Les quatre formes juridiques n'ont ni les mêmes pièces ni les mêmes règles. Une classe par cas, choisie par une fabrique, évite le `if` à quatre branches qui grossit à chaque évolution.

---

## 6. Où atterrit chaque champ

| Champ du formulaire | Table | Colonne |
|---|---|---|
| `beneficiaires[i][telephone]` | `acteurs` + `beneficiaires` | `telephone` |
| `beneficiaires[i][nom]` | `acteurs` | `nom` |
| `beneficiaires[i][email]` | `beneficiaires` | `email` |
| `beneficiaires[i][superficies][n]` | `cultures` | `id_type_culture`, `superficie` |
| `appuis[n]` | `lignes_demandes` | `id_type_appui`, `details` |
| `pieces[n]` | `pieces_demandes` | `chemin_fichier` |
| `beneficiaires[i][localite_id]` | **aucune** | perdu à l'écriture |
| `type_entite` | **aucune** | validé, jamais stocké |

Les colonnes `telephone` et `email` de `beneficiaires` ont été ajoutées par une migration dédiée : elles conservent le contact *tel qu'il a été déclaré pour cette demande*, indépendamment de la fiche acteur qui, elle, peut évoluer.

---

## 7. Ce qui empêche aujourd'hui l'enregistrement

La vue est fonctionnelle. Le chemin d'écriture ne l'est pas : le schéma ne correspond pas à ce que le contrôleur tente d'insérer.

### Résolus

**`demandes.debut_periode_mise_en_place`** — colonne non nulle jamais renseignée, qui rejetait l'insertion. Un champ date a été ajouté en tête de l'étape 3 (`debut_periode_mise_en_place`), validé en `required|date`.

**`cultures.lib_culture`** — même défaut, résolu en supprimant la colonne : le libellé du type de culture la rendait redondante.

**`cultures.superficie`** — passée de `integer` à `decimal(8,2)`. Elle tronquait silencieusement les parcelles de moins d'un hectare.
→ `2026_08_12_000001_adjust_cultures_table.php`

### Ouverts

**Les demandes sont créées sans statut — par choix.**
La table `demandes` n'a pas de colonne `id_statut_demande` et il a été décidé de ne pas en ajouter. La table `statuts_demandes` et l'enum `StatutDemandeEnum` existent donc sans être reliés au flux de création.

Conséquence à garder en tête : `Demande::scopePourUtilisateur()` filtre sur `id_statut_demande` pour le rôle DP, et `DemandeContext` résout l'état d'une demande à partir de ce même champ. Les deux échoueront tant que le statut n'est pas matérialisé d'une façon ou d'une autre.
→ `app/Models/Demande.php`

### À décider / vérifier

**La localité n'a pas de destination.** Le champ est saisi et validé, mais ni `beneficiaires` ni `cultures` n'ont de colonne pour l'accueillir. Le plus cohérent serait `id_localite` sur `beneficiaires` — reste à confirmer qu'un bénéficiaire ne cultive que dans une seule localité.

**Le téléphone sert de clé sans être unique.** La recherche d'acteur se fait sur `telephone`, sans index unique sur la colonne. Deux fiches au même numéro rendraient la résolution non déterministe.

**La migration `user_id` nullable est à relire.** Elle enchaîne un `dropUnique`, un `foreign(...)->change()` et un `nullable()->change()` sur la même colonne. L'ordre et la ligne `foreign()` sont douteux : à tester sur une base jetable avant de l'exécuter ailleurs.
→ `2026_08_11_make_user_id_nullable_in_acteurs.php`

---

## 8. Reste à traiter

- décider comment matérialiser le statut d'une demande, puisqu'aucune colonne ne le porte (voir §7) ;
- trancher la question de la localité — une par bénéficiaire, ou une par parcelle ?
- décider où conserver `type_entite` : la table `types_acteurs` existe déjà et pourrait l'accueillir ;
- filtrer les pièces justificatives selon le type d'acteur (la liste complète est affichée quel que soit le profil) ;
- implémenter les trois stratégies de création manquantes, la fabrique renvoyant toujours la même.

---

*État du dépôt sur `main` au 11 août 2026, après restructuration du wizard en deux étapes.*
