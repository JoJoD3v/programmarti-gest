#!/bin/bash
# Deploy manuale su cPanel. Uso: bash ~/programmarti-gest/deploy-programmarti.sh
# Se la repo non e' in ~/programmarti-gest: APP_DIR=/home/CPUSER/altra-cartella bash deploy-programmarti.sh
# Gli asset (public/build) sono committati: sull'hosting non serve Node.
set -e

PHP="${PHP:-/opt/cpanel/ea-php84/root/usr/bin/php}"
COMPOSER="${COMPOSER:-/usr/local/bin/composer}"
APP_DIR="${APP_DIR:-$HOME/programmarti-gest}"

cd "$APP_DIR"

$PHP artisan down
trap '$PHP artisan up' EXIT

git pull origin main
$PHP "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction
$PHP artisan migrate --force
$PHP artisan storage:link 2>/dev/null || true
$PHP artisan optimize:clear
$PHP artisan optimize
$PHP artisan queue:restart

echo "Deploy completato"
