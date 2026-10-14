#!/bin/sh
# Render attend qu'un port s'ouvre dans les premières minutes. Les migrations et le seed, eux, peuvent durer
# bien plus (base distante, processeur partagé) : Apache démarre donc tout de suite, et l'installation se fait
# pendant que le site répond « 503 en maintenance » (sauf /up, le contrôle de santé de Render).
# L'installation terminée, le site repasse en ligne tout seul. Si elle échoue, il reste en maintenance
# et l'erreur est dans les journaux, plutôt que de servir une application à moitié installée.

chown -R www-data:www-data storage bootstrap/cache

# Les commandes d'installation tournent sous l'utilisateur d'Apache : les fichiers qu'elles créent
# (caches, vues compilées) restent écrivables par lui.
artisan() { runuser -u www-data -- php artisan "$@"; }

# Le lien public/storage se crée avec les droits du propriétaire de public/ (root).
php artisan storage:link --force || true

(
    set -e
    artisan down --retry=15
    # Les migrations d'abord : avec CACHE_STORE=database, vider le cache avant sa création échouerait.
    artisan migrate --force
    artisan optimize:clear

    # Rôles et permissions : idempotent, garde à jour les droits ajoutés par les nouvelles versions.
    artisan db:seed --class=RolePermissionSeeder --force

    # SEED_ON_DEPLOY=true pour peupler une base vide (référentiels + comptes de démonstration).
    if [ "$SEED_ON_DEPLOY" = "true" ]; then
        artisan db:seed --force
    fi

    artisan filament:optimize || true
    artisan optimize

    artisan up
    echo "[entrypoint] installation terminée : le site est en ligne."
) || echo "[entrypoint] ÉCHEC de l'installation : le site reste en maintenance (503). Voir l'erreur ci-dessus." >&2 &

exec "$@"
