#!/bin/sh
set -eu

: "${APP_KEY:?Set a stable APP_KEY in the deployment variables.}"
mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
php artisan storage:link --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec docker-php-entrypoint "$@"
