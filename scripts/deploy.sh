#!/usr/bin/env bash

set -Eeuo pipefail
umask 002

PROJECT_ROOT="${DEPLOY_PATH:-/var/www/estate-agent}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
LOCK_FILE="$PROJECT_ROOT/storage/framework/deploy.lock"

cd "$PROJECT_ROOT"

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
    echo "Another deployment is already running."
    exit 0
fi

maintenance_enabled=false

finish() {
    if [[ "$maintenance_enabled" == true ]]; then
        php artisan up || true
    fi
}

trap finish EXIT

php artisan down --retry=30
maintenance_enabled=true

echo "Fetching $DEPLOY_BRANCH..."
git -c safe.directory="$PROJECT_ROOT" pull --ff-only origin "$DEPLOY_BRANCH"

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --no-audit --no-fund
npm run build

php artisan migrate --force
php artisan optimize:clear
php artisan advertisements:prepare-home
php artisan optimize
php artisan queue:restart || true

php artisan up
maintenance_enabled=false
trap - EXIT

echo "Deployment completed successfully."
