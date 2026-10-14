#!/bin/sh
set -e

# Le disque de Render est éphémère : on recrée à chaque démarrage ce qui en dépend.
php artisan storage:link --force || true
php artisan optimize:clear
php artisan migrate --force

# SEED_ON_DEPLOY=true pour peupler une base vide (référentiels + comptes de démonstration).
if [ "$SEED_ON_DEPLOY" = "true" ]; then
    php artisan db:seed --force
fi

php artisan filament:optimize || true
php artisan optimize

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
