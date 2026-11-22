#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is required"
  exit 1
fi

php artisan config:clear
php artisan storage:link --force >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
  php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
