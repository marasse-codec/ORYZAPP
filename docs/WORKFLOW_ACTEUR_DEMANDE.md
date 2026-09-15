# 🔄 Workflow: Acteur → Demande

## Vue d'Ensemble

```
┌─────────────────────────────────────────────────────────────────┐
│                    PROCESSUS DE CRÉATION DEMANDE                │
└─────────────────────────────────────────────────────────────────┘

    ÉTAPE 1: AUTHENTIFICATION
    ├─ User crée compte (register)
    └─ User est lié à un Acteur (user_id)

    ÉTAPE 2: CREATION DEMANDE
    ├─ Acteur accède au formulaire /demandes/create
    ├─ Sélectionne les appuis voulus
    ├─ Saisit les types de cultures et superficies
    └─ Soumet → POST /demandes/store

    ÉTAPE 3: STOCKAGE EN BASE
    ├─ Demande créée avec statut = "enAttenteDP"
    ├─ LigneDemandes créées (1 par appui)
    ├─ DemandeTypeCultures créées (1 par culture)
    └─ DB COMMIT ✅

    ÉTAPE 4: WORKFLOW POST-DEMANDE (Admin)
    ├─ DP valide → statut = "enAttenteDir"
    ├─ Direction valide → statut = "enAttenteRec"
    ├─ Commission attribue → id_commission assigné
    └─ Fin du flux d'ici
```

## Flux de Données Détaillé

### 1️⃣ CRÉATION DE L'UTILISATEUR

```php
// Créé via RegisterController (Laravel Breeze)
$user = User::create([
    'email' => 'agriculteur@email.com',
    'password' => bcrypt('password'),
]);
```

| users |
|-------|
| id: 1 |
| email: agriculteur@email.com |
| password: hashed |

---

### 2️⃣ CRÉATION DE L'ACTEUR

```php
// Créé après registration ou via Admin
$acteur = Acteur::create([
    'user_id' => $user->id,  // FK → users.id
    'nom' => 'Dupont Jean',
    'adresse' => '123 Rue Principale',
    'telephone' => '+223 XXXX XXXX',
]);
```

| acteurs |
|---------|
| id_acteur: 1 |
| user_id: 1 → users.id |
| nom: Dupont Jean |
| adresse: 123 Rue Principale |
| telephone: +223 XXXX XXXX |

---

### 3️⃣ CRÉATION DE LA DEMANDE (POST /demandes/store)

```php
// DemandeCreationStrategyFactory::create() → DemandeProducteurIndividuelCreationStrategy
// Strategy valide et crée la demande

$demande = Demande::create([
    'id_acteur' => 1,                    // FK → acteurs.id_acteur
    'date_demande' => now()->date(),
    'debut_periode_mise_en_place' => '2026-09-01',
    'fin_periode_mise_en_place' => '2026-11-30',
    'id_statut_demande' => 1,            // "enAttenteDP"
    'id_commission' => null,             // Sera assigné plus tard
    'commentaire_rejet' => null,
]);
```

| demandes |
|----------|
| id_demande: 1 |
| id_acteur: 1 → acteurs.id_acteur |
| date_demande: 2026-08-10 |
| debut_periode_mise_en_place: 2026-09-01 |
| fin_periode_mise_en_place: 2026-11-30 |
| id_statut_demande: 1 → statuts_demandes.id_statut_demande |
| id_commission: NULL |

---

### 4️⃣ CRÉATION DES LIGNES DEMANDE (Appuis)

```php
// Pour chaque appui demandé
LigneDemande::create([
    'id_demande' => 1,        // FK → demandes.id_demande
    'id_appui' => 5,          // FK → appuis.id_appui (engrais, semences, etc.)
    'details' => 'Engrais NPK 20-20-20',
]);
```

| lignes_demandes |
|-----------------|
| id_ligne_demande: 1 |
| id_demande: 1 → demandes.id_demande |
| id_appui: 5 → appuis.id_appui |
| details: Engrais NPK 20-20-20 |

---

### 5️⃣ CRÉATION DES TYPES DE CULTURES

```php
// Pour chaque culture avec superficia
DemandeTypeCulture::create([
    'id_demande' => 1,         // FK → demandes.id_demande
    'id_type_culture' => 3,    // FK → types_cultures.id_type_culture (riz, maïs, etc.)
    'superficie' => 2,         // en hectares
]);
```

| demandes_types_cultures (Pivot) |
|---------------------------------|
| id: 1 |
| id_demande: 1 → demandes.id_demande |
| id_type_culture: 3 → types_cultures.id_type_culture |
| superficie: 2 |

---

## 📊 Vue Globale des Tables (Après Création)

```
USERS
├─ id: 1
├─ email: agriculteur@email.com
└─ role: acteur

     ↓ (user_id)

ACTEURS
├─ id_acteur: 1
├─ user_id: 1
├─ nom: Dupont Jean
└─ telephone: +223 XXXX XXXX

     ↓ (id_acteur)

DEMANDES
├─ id_demande: 1
├─ id_acteur: 1
├─ id_statut_demande: 1 (enAttenteDP)
├─ date_demande: 2026-08-10
└─ id_commission: NULL

     ├─ (id_demande)
     │
     ├→ LIGNES_DEMANDES
     │  ├─ id_ligne_demande: 1
     │  ├─ id_appui: 5
     │  └─ details: Engrais NPK...
     │
     └→ DEMANDES_TYPES_CULTURES
        ├─ id_demande: 1
        ├─ id_type_culture: 3
        └─ superficie: 2

STATUTS_DEMANDES
├─ id_statut_demande: 1
└─ lib_statut_demande: "En attente de reponse du DP"

TYPES_CULTURES
├─ id_type_culture: 3
└─ lib_type_culture: "Riz"

APPUIS
├─ id_appui: 5
├─ lib_appui: "Engrais"
└─ id_type_appui: 2
```

---

## 🔍 Requêtes Éloquent Équivalentes

### Charger Une Demande Complète
```php
$demande = Demande::with([
    'acteur',
    'statutDemande',
    'commissionAttribution',
    'lignes.appui',
    'typesCultures'
])->find(1);

// Accès:
$demande->acteur->nom;                    // "Dupont Jean"
$demande->statutDemande->lib_statut_demande;  // "En attente de reponse du DP"
$demande->lignes[0]->appui->lib_appui;   // "Engrais"
$demande->typesCultures[0]->lib_type_culture; // "Riz"
$demande->typesCultures[0]->pivot->superficie; // 2
```

### Charger Les Demandes d'Un Acteur
```php
$acteur = Acteur::with('demandes.statutDemande')->find(1);
$acteur->demandes;  // Collection of Demande

foreach ($acteur->demandes as $demande) {
    echo $demande->statutDemande->lib_statut_demande;
}
```

### Charger les Détails d'Une Demande (Admin)
```php
$demande = Demande::with([
    'acteur.user',
    'lignes' => fn($q) => $q->with('appui.typeAppui'),
    'typesCultures',
    'commissionAttribution'
])->find(1);
```

---

## 📝 Validation des Données (DemandeController)

```php
$validatedData = $request->validate([
    'demande_acteur' => 'required',           // user_id
    'demande_corps' => 'nullable|string',     // (non utilisé)
    'demande_appuis' => 'nullable|array',     // Array of appuis
    'demande_appuis.*.id_appui' => 'required_with:...',
    'demande_appuis.*.details' => 'nullable|string',
    'demande_appuis_materiels' => 'nullable|array',  // Material appuis
    'demande_supercifies_types_cultures' => 'required', // Array[id_type_culture => superficie]
    'demande_date' => 'required|date',
    'demande_debut_periode_mise_en_place' => 'required|date',
    'demande_fin_periode_mise_en_place' => 'required|date',
]);
```

---

## 🚀 Statuts de Demande (Workflow)

```
1. enAttenteDP (En attente de DP)
   ├─ DP valide → 2. enAttenteDir
   └─ DP rejette → 4. Rejetée

2. enAttenteDir (En attente de Direction)
   ├─ Dir valide → 3. enAttenteRec
   └─ Dir rejette → 4. Rejetée

3. enAttenteRec (En attente de Récupération)
   ├─ Commission attribue → Demande complétée
   └─ Pas d'attribution → Status reste enAttenteRec

4. Rejetée (Définitif)
   └─ Pas de retour en arrière
```

---

## ✅ Checklist Implémentation

- [x] User → Acteur relation (user_id)
- [x] Acteur → Demande relation (id_acteur)
- [x] Demande → StatutDemande relation (id_statut_demande)
- [x] Demande → CommissionAttribution relation (id_commission)
- [x] Demande → LigneDemande relation (1:n)
- [x] LigneDemande → Appui relation (n:1)
- [x] Demande ↔ TypeCulture relation (n:m via pivot)
- [x] DemandeTypeCulture attributes (id, id_demande, id_type_culture, superficie)
- [x] Controller strategy pattern (DemandeProducteurIndividuelCreationStrategy)
- [x] Transaction DB pour atomicité
- [x] Enums for StatutDemande
- [x] Scopes for filtering (scopePourUtilisateur)
- [x] Model relations properly defined

---

## 🧪 Tests Manuels

```bash
# 1. Créer un user
php artisan tinker
> $user = User::create(['email' => 'test@example.com', 'password' => bcrypt('test123')])

# 2. Créer un acteur
> $acteur = Acteur::create(['user_id' => $user->id, 'nom' => 'Test', 'adresse' => 'Addr', 'telephone' => '1234'])

# 3. Créer une demande
> $demande = Demande::create([
    'id_acteur' => $acteur->id_acteur,
    'date_demande' => now(),
    'debut_periode_mise_en_place' => now(),
    'fin_periode_mise_en_place' => now()->addMonths(3),
    'id_statut_demande' => 1
  ])

# 4. Vérifier les relations
> $acteur->demandes
> $demande->acteur
> $demande->statutDemande
> $demande->typesCultures
```
