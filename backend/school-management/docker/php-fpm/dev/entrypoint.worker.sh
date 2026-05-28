#!/bin/bash
set -e

echo "Iniciando worker de Laravel..."

echo "Limpiando cachés..."
php artisan optimize:clear || echo "No se pudo limpiar"

echo "Iniciando worker..."
php artisan queue:work redis \
    --queue=cache,high,emails,low,default,notifications,processing \
    --sleep=3 \
    --backoff=5 \
    --tries=3 \
    --timeout=120 \
    --memory=256 \
    --max-jobs=100 \
    --max-time=3600 &

echo "Tareas programadas:"
php artisan schedule:list || true


echo "Iniciando scheduler..."
LOG_FILE="/var/www/storage/logs/scheduler-$(date '+%Y-%m-%d').log"
while true; do
    {
        echo "---- $(date '+%Y-%m-%d %H:%M:%S') Ejecutando schedule:run ----"
        php artisan schedule:run
        echo "---- Esperando 60 segundos ----"
    } | tee -a "$LOG_FILE"
    sleep 60
done
