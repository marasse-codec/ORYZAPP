# Sauvegarde des données et brouillon de saisie

Deux mécanismes distincts : la **sauvegarde système** (SNF05, serveur) et le **brouillon de saisie** (formulaire de demande).
Les schémas sont en Mermaid (rendus par GitHub, GitLab, VS Code, etc.).

## 1. Architecture — sauvegarde système

```mermaid
flowchart LR
    CRON[Cron serveur<br/>php artisan schedule:run] --> SCH[routes/console.php<br/>Schedule]
    SCH -->|02:00| RUN[backup:run]
    SCH -->|03:00| MON[backup:monitor]
    RUN --> DUMP[mysqldump<br/>mariadb-client]
    DUMP --> DB[(MySQL aderiz_app)]
    RUN --> PJ[storage/app/public<br/>pièces justificatives]
    RUN --> ZIP[Archive .zip<br/>chiffrable]
    ZIP --> DISK[Disque « sauvegardes »<br/>storage/app/backups]
    MON --> DISK
    RUN -. échec .-> MAIL[Courriel administrateur]
    MON -. périmée > 1 j .-> MAIL
```

Configuration : `config/backup.php` (contenu, disque, rétention, notifications), disque dans `config/filesystems.php`,
`mariadb-client` dans `infra/Dockerfile`. Variables : `BACKUP_DISKS`, `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_NOTIFY_MAIL`, `DB_DUMP_EXTRA_OPTION`.

### Séquence d'une sauvegarde

```mermaid
sequenceDiagram
    participant S as Planificateur
    participant B as backup:run
    participant D as mysqldump
    participant F as Pièces (public)
    participant Z as Disque sauvegardes
    participant A as Administrateur
    S->>B: 02:00
    B->>D: dump de la base
    D-->>B: database.sql
    B->>F: parcourt storage/app/public
    B->>B: crée l'archive zip
    alt succès
        B->>Z: copie l'archive
    else échec
        B-->>A: courriel BackupHasFailed
    end
    S->>Z: 03:00 backup:monitor (âge ≤ 1 j, taille ≤ 5 Go)
```

> Aucun nettoyage automatique : `backup:clean` n'est pas planifié, toutes les archives sont conservées.
> `backup:monitor` prévient quand les archives dépassent 5 Go ; supprimer alors les plus anciennes à la main.

## 2. Brouillon de saisie

### Composants

```mermaid
flowchart TB
    subgraph Navigateur
        V[demandes/create.blade.php<br/>Alpine : init, instantane,<br/>sauvegarderBrouillon, reprendreBrouillon]
    end
    subgraph Laravel
        R[routes/web.php<br/>demandes.brouillon.*]
        C[BrouillonDemandeController<br/>show / save / destroy]
        DC[DemandeController::store]
        M[BrouillonSaisie]
    end
    DBT[(brouillons_saisies)]
    V -->|GET / PUT / DELETE JSON| R --> C --> M --> DBT
    V -->|POST formulaire + fichiers| DC
    DC -->|supprime le brouillon| M
```

### Classes

```mermaid
classDiagram
    class User { +id }
    class BrouillonSaisie {
        +id_brouillon
        +id_user
        +type  «demande»
        +donnees  array
        +TYPE_DEMANDE
    }
    class BrouillonDemandeController {
        +show(Request) JsonResponse
        +save(Request) JsonResponse
        +destroy(Request) JsonResponse
    }
    class DemandeController { +store(request, service) }
    User "1" --> "0..*" BrouillonSaisie : possède
    BrouillonDemandeController ..> BrouillonSaisie
    DemandeController ..> BrouillonSaisie : supprime après création
```

### MLD

```mermaid
erDiagram
    users ||--o{ brouillons_saisies : "id_user (FK fk_brouillons_user, CASCADE)"
    brouillons_saisies {
        bigint id_brouillon PK
        bigint id_user FK
        varchar30 type
        json donnees
        timestamp created_at
        timestamp updated_at
    }
```

Contrainte `uq_brouillons_user_type` : un seul brouillon par utilisateur et par type de formulaire (extensible à d'autres formulaires).

### Séquence

```mermaid
sequenceDiagram
    actor U as Utilisateur
    participant P as Page create (Alpine)
    participant C as BrouillonDemandeController
    participant DB as brouillons_saisies
    U->>P: ouvre « Nouvelle demande »
    P->>C: GET /demandes/brouillon
    C->>DB: SELECT (id_user, type)
    DB-->>P: brouillon ou null
    opt brouillon trouvé
        P-->>U: bandeau Reprendre / Ignorer
        U->>P: Reprendre (restaure état + champs)
    end
    loop à chaque saisie (anti-rebond 1,5 s)
        P->>C: PUT /demandes/brouillon {donnees}
        C->>DB: upsert
        C-->>P: enregistre_le
    end
    U->>P: Enregistrer la demande
    P->>C: POST /demandes (fichiers inclus)
    C->>DB: création de la demande puis DELETE du brouillon
```

Limites : les fichiers téléversés ne sont pas conservés (à rejoindre après reprise) ; seule la création de demande est couverte
(pas l'édition ni la saisie en série) ; taille maximale 100 000 octets.
