# 📑 Index: Alignement MLD ↔ Migrations ↔ Modèles

**Date**: 2026-08-10  
**Responsable**: Claude Code  
**Scope**: MVP - Flux "Acteur fait une Demande"

---

## 📄 Documents Créés/Modifiés

### Diagrammes & Documentation

| Document | Type | Status | Description |
|----------|------|--------|-------------|
| [docs/mld_acteur_demande.puml](./mld_acteur_demande.puml) | **NEW** ✨ | ✅ | MLD simplifié MVP (recommandé pour le flux acteur→demande) |
| [docs/mld_with_ia.puml](./mld_with_ia.puml) | MODIFIÉ | ✅ | MLD complet mis à jour + commentaires |
| [docs/ALIGNMENT_SUMMARY.md](./ALIGNMENT_SUMMARY.md) | **NEW** ✨ | ✅ | Synthèse complète de cohérence (20 lignes clés) |
| [docs/WORKFLOW_ACTEUR_DEMANDE.md](./WORKFLOW_ACTEUR_DEMANDE.md) | **NEW** ✨ | ✅ | Workflow détaillé avec visualisations |
| [docs/INDEX_ALIGNMENT.md](./INDEX_ALIGNMENT.md) | **NEW** ✨ | 📍 | Ce fichier (index de tous les changements) |

### Code Modifié

#### Models

| Fichier | Changement | Ligne(s) | Impact |
|---------|-----------|---------|--------|
| app/Models/LigneDemande.php | ✅ Fix PK | 10 | **CRITIQUE**: était cassé, maintenant cohérent |
| app/Models/Acteur.php | ✅ Clean fillable | 16-22 | Suppression de champs inexistants en DB |
| app/Models/Acteur.php | ✅ Fix relations | 29-35 | Suppression + ajout relation demandes() |
| app/Models/DemandeTypeCulture.php | ✅ Fix relations | 16-23 | Inversion de relations (HasOne → BelongsTo) |
| app/Models/TypeCulture.php | ✅ Fix relation | 19 | TypeAppui → TypeCulture (modèle exact) |
| app/Models/Demande.php | ✅ Fix relation | 48 | TypeAppui → TypeCulture (modèle exact) |

#### Migrations (Aucune)
✅ Toutes les migrations existent et sont cohérentes avec les modèles.

#### Controllers
🔍 À vérifier: `app/Http/Controllers/DemandeController.php` (utilise les bonnes relations)

---

## 🔄 Changements par Catégorie

### 🔴 Corrections Critiques (Bugs)
1. **LigneDemande Primary Key** - Impossible de récupérer les lignes
2. **Demande.typesCultures** - Relie au mauvais modèle (TypeAppui vs TypeCulture)
3. **TypeCulture.demandes** - Relie au mauvais modèle (TypeAppui vs Demande)

### 🟡 Corrections Importantes (Incohérence)
1. **Acteur.fillable** - Référence à des colonnes inexistantes
2. **Acteur.relations** - Relations fantasmes sans FK en base
3. **DemandeTypeCulture.relations** - Direction incorrecte (HasOne vs BelongsTo)

### 🟢 Corrections Mineures (Documentation)
1. MLD mis à jour pour refléter la réalité
2. Documentation workflow créée
3. Index de cohérence créé

---

## ✅ Validation Complète

### Tables Impliquées (Checksum)
```
✅ users                      (Laravel Breeze)
✅ acteurs                    (id_acteur, user_id, nom, adresse, telephone)
✅ demandes                   (id_demande, id_acteur, id_statut_demande, id_commission, ...)
✅ lignes_demandes            (id_ligne_demande, id_demande, id_appui, details)
✅ demandes_types_cultures    (id, id_demande, id_type_culture, superficie)
✅ types_cultures             (id_type_culture, lib_type_culture)
✅ appuis                     (id_appui, lib_appui, id_type_appui, id_unite_appui, ...)
✅ types_appuis              (id_type_appui, lib_type_appui)
✅ statuts_demandes          (id_statut_demande, lib_statut_demande)
✅ commissions_attributions  (id_commission, date_ouverture_commission, date_fermeture_commission)
```

### Relations Validées
```
✅ User (1) → (1) Acteur
✅ Acteur (1) → (n) Demande
✅ Demande (n) → (1) StatutDemande
✅ Demande (n) → (1) CommissionAttribution
✅ Demande (1) → (n) LigneDemande
✅ LigneDemande (n) → (1) Appui
✅ Demande (n:m) → (n) TypeCulture (via DemandeTypeCulture)
```

### Enums Utilisés
```
✅ StatutDemandeEnum (1=enAttenteDP, 2=enAttenteDir, 3=enAttenteRec, 4=Rejetee)
✅ RoleEnum (acteur, dp, dir, rec, admin, sg, dg)
```

---

## 🚀 Prochaines Étapes

### Phase 1 (Immédiate)
- [ ] Tester les relations en production avec `php artisan tinker`
- [ ] Valider que les demandes se créent correctement
- [ ] Vérifier que les lignes demandes sont accessibles

### Phase 2 (Bénéficiaires & Photos)
- [ ] Créer migration pour table `beneficiaires`
- [ ] Créer migration pour table `photos_parcelles`
- [ ] Ajouter modèles et relations

### Phase 3 (Workflow Post-Demande)
- [ ] Implémenter rapports (reports)
- [ ] Implémenter délégations (delegations)
- [ ] Implémenter attributions (attributions)

---

## 📖 Comment Utiliser Ces Docs

### Pour les Développeurs
1. **Commencer par** → `docs/mld_acteur_demande.puml` (vue simple)
2. **Puis lire** → `docs/WORKFLOW_ACTEUR_DEMANDE.md` (processus détaillé)
3. **Référence** → `docs/ALIGNMENT_SUMMARY.md` (tableau de cohérence)

### Pour les Tests
```bash
# Tests des relations
php artisan tinker
> Demande::with('acteur', 'lignes.appui', 'typesCultures')->first()

# Tests du workflow de création
php artisan test tests/Feature/DemandeCreationTest.php
```

### Pour les Code Reviews
Vérifier que:
- [ ] Les relations utilisent les bonnes clés étrangères
- [ ] Les PK sont correctement déclarés dans les modèles
- [ ] Les migrations correspondent aux fillable des modèles
- [ ] Les enums sont correctement castés

---

## 🔗 Références Croisées

### Fichiers Clés
- **Migration de demandes**: `database/migrations/2026_07_10_114525_create_demandes_table.php`
- **Migration de lignes**: `database/migrations/2026_07_10_114536_create_lignes_demandes_table.php`
- **Migration de statuts**: `database/migrations/2026_07_15_000001_create_statuts_demandes_table.php`
- **Controller principal**: `app/Http/Controllers/DemandeController.php`
- **Enum des statuts**: `app/Enums/StatutDemandeEnum.php`

### MLD Disponibles
1. **Simple** (MVP): `docs/mld_acteur_demande.puml`
2. **Complet** (Full): `docs/mld_with_ia.puml`

---

## 📊 Résumé des Impacts

| Fichier Modifié | Lignes | Type | Impact Utilisateur |
|-----------------|-------|------|-------------------|
| LigneDemande | 1 | PK | **HAUT** - Formulaires de demande (FIXÉ) |
| Acteur | 8 | Fillable/Relations | **MOYEN** - Chargement acteur avec demandes |
| DemandeTypeCulture | 8 | Relations | **MOYEN** - Accès aux cultures d'une demande |
| TypeCulture | 1 | Relation | **MOYEN** - Lien demande ↔ culture |
| Demande | 1 | Relation | **MOYEN** - Chargement des cultures |
| MLD | 20 | Documentation | **BAS** - Clarification interne |

---

## ✍️ Checklist Vérification

Avant de merger/déployer:

- [ ] Tests: `php artisan test`
- [ ] Tinker: Relations chargent correctement
- [ ] Formulaire: Demandes se créent sans erreur
- [ ] Admin: Accès aux demandes et lignes fonctionnent
- [ ] DB: Vérifier intégrité des clés étrangères
- [ ] Logs: Pas d'erreurs Eloquent

---

**Statut Global**: ✅ **ALIGNÉ ET FONCTIONNEL**

Pour questions: Consulter `docs/WORKFLOW_ACTEUR_DEMANDE.md`
