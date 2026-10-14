# Déploiement sur Render (offre gratuite)

Fichiers : `Dockerfile`, `docker/entrypoint.sh`, `render.yaml` (Blueprint).

## Premier déploiement

1. Pousser le dépôt sur GitHub, puis Render → **New → Blueprint**.
2. Renseigner `APP_KEY`, `APP_URL`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`.
3. Mettre `SEED_ON_DEPLOY=true` pour le premier démarrage (référentiels + comptes de démonstration), puis le repasser à `false`.

Au démarrage, `docker/entrypoint.sh` joue les migrations et resynchronise les rôles et permissions
(`RolePermissionSeeder`, idempotent) : une permission ajoutée par une nouvelle version est donc donnée à l'admin sans geste.

## Planificateur (sauvegardes, rappels, résumé quotidien)

Render gratuit n'a ni cron ni worker, et le service s'endort après 15 min sans visite : `schedule:work` ne peut pas tourner.
Solution : un service de ping appelle l'application chaque minute, comme le ferait `* * * * * php artisan schedule:run`.

1. Render → service → Environment : lire la valeur générée de `CRON_TOKEN`.
2. Sur [cron-job.org](https://cron-job.org) (gratuit) créer une tâche :
   - URL : `https://<votre-service>.onrender.com/planification/executer`
   - Méthode : `POST`, fréquence : **chaque minute**
   - En-tête : `Authorization: Bearer <CRON_TOKEN>`
3. Contrôle : la réponse doit être `202 {"statut":"lancé"}`. Sans jeton ou avec un mauvais jeton : `404` / `401`.

Un ping chaque minute garde aussi le service éveillé (750 h gratuites par mois ≥ 744 h d'un mois de 31 jours).
Tâches ainsi exécutées (cf. `routes/console.php`) : sauvegarde 2 h, nettoyage 2 h 30, contrôle 3 h,
résumé quotidien 7 h, rappels du lundi 8 h.

Sans `CRON_TOKEN`, la route n'existe pas (404).

## Sauvegardes

- **Contenu** : dump de la base (`pg_dump` en PostgreSQL, `mysqldump` en MySQL) + pièces téléversées (`storage/app/public`).
- **Écran** : Administration → Système → **Sauvegardes** (santé, liste, téléchargement, lancement à la demande).
- **Rétention** : 7 jours complets, puis 16 jours quotidiens, 8 semaines, 4 mois, 2 ans.
- **Alertes** : e-mail à `BACKUP_NOTIFY_MAIL` en cas d'échec ou de sauvegarde de plus d'un jour. Rien en cas de succès.
- **Chiffrement** : définir `BACKUP_ARCHIVE_PASSWORD` (les données personnelles des acteurs y figurent). Sans ce mot de passe, l'archive ne se restaure pas : le conserver ailleurs.

### Attention : le disque de Render est effacé à chaque déploiement

Le disque `sauvegardes` est local. Sur Render, les archives disparaissent au redéploiement ou au réveil.
Pour qu'elles survivent, envoyer les archives sur un stockage externe compatible S3 (Cloudflare R2, Backblaze B2, AWS S3) :

```
BACKUP_DISKS=sauvegardes,s3
AWS_ACCESS_KEY_ID=...  AWS_SECRET_ACCESS_KEY=...  AWS_DEFAULT_REGION=...  AWS_BUCKET=...
AWS_ENDPOINT=...       AWS_USE_PATH_STYLE_ENDPOINT=true   # R2 / B2
```

La base PostgreSQL gratuite de Render est supprimée après 30 jours : télécharger une sauvegarde avant l'échéance.

### Restaurer

```bash
unzip -P "$BACKUP_ARCHIVE_PASSWORD" archive.zip      # décompresse db-dumps/ et storage/app/public/
psql "$DATABASE_URL" < db-dumps/postgresql-<base>.sql   # MySQL : mysql < db-dumps/mysql-<base>.sql
```

Remettre ensuite les pièces dans `storage/app/public/`.
