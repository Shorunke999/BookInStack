#!/usr/bin/env bash
set -e

echo "==> Installing dependencies"
composer install --no-dev --no-interaction --prefer-dist --working-dir=/var/www/html

echo "==> Generating app key if missing"
php artisan key:generate --no-interaction --force

echo "==> Running migrations"
php artisan migrate --force          # NEVER migrate:fresh — that wipes your data

echo "==> Clearing and caching"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Linking storage"
php artisan storage:link --force

echo "==> Done"