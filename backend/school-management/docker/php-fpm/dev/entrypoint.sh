#!/bin/bash
set -e

echo "APP_ROLE=${APP_ROLE:-undefined}"
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

if [ "$APP_ROLE" = "app" ]; then
  echo "Inicializando Laravel..."

  rm -rf bootstrap/cache/*.php

  php artisan config:cache || true
fi

echo "Laravel listo."

exec "$@"
