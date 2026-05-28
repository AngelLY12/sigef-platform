#!/bin/sh
set -e

SLEEP_TIME=300  
echo "Durmiendo $SLEEP_TIME segundos hasta que Laravel esté listo..."
sleep $SLEEP_TIME

echo "Iniciando Nginx..."
exec nginx -g "daemon off;"
