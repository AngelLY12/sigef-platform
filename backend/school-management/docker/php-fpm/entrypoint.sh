#!/bin/bash
set -e
echo "APP_ROLE=${APP_ROLE:-undefined}"

if [ "$APP_ROLE" = "app" ]; then
  echo "Inicializando Laravel (APP)..."

  echo "Limpiando cachés..."
  php artisan optimize:clear || true
  php artisan config:clear || true
  php artisan config:cache || true
  php artisan storage:link || true

  ATTEMPTS=0
  MAX_ATTEMPTS=10
  echo "Ejecutando migraciones..."
  until php artisan migrate --force; do
    ATTEMPTS=$((ATTEMPTS+1))
    if [ $ATTEMPTS -ge $MAX_ATTEMPTS ]; then
        echo "No se pudo conectar a la base de datos tras varios intentos."
        exit 1
    fi
    echo "Esperando base de datos... Intento $ATTEMPTS/$MAX_ATTEMPTS"
    sleep 5
  done

  echo "Ejecutando seeders..."
  php artisan db:seed --force || true
fi

echo "Laravel listo. Iniciando proceso principal..."

exec "$@"
