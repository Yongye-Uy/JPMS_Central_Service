#!/bin/sh
set -e

# nginx runs in a separate container with its own filesystem — share
# public/ via a volume so it can serve static files directly (see
# docker-compose.yml and the matching note in Frontend/docker/entrypoint.sh).
mkdir -p /var/www/html/public-shared
cp -r /var/www/html/public/. /var/www/html/public-shared/

# Central-Service is the only app that owns migrations — runs them on every
# boot (idempotent: Laravel skips already-applied migrations). The seeder
# is also idempotent (firstOrCreate everywhere), so it's safe to run on
# every boot too — this is what creates the demo accounts
# (admin/editor/author/reviewer/reader@jpms.com, password "password").
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache

exec "$@"
