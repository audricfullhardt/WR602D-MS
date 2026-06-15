#!/usr/bin/env bash
set -e

cd /var/www/html

echo "[entrypoint] Préparation du microservice mailer (APP_ENV=${APP_ENV:-prod})..."

# Permissions sur le cache / logs
mkdir -p var/cache var/log
chown -R www-data:www-data var

# Cache prod (auto-scripts désactivés au build, on chauffe ici avec l'env présent)
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

chown -R www-data:www-data var

echo "[entrypoint] Démarrage des services."
exec "$@"
