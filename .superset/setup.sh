#!/usr/bin/env bash
# Get a new worktree ready: dependencies, its own .env and SQLite database, Wayfinder routes and a Herd site. Safe to run again.
set -euo pipefail
. ./.superset/site-name.sh

composer install --no-interaction
pnpm install --frozen-lockfile

# Copied from the main checkout only once; from then on it belongs to this worktree.
[ -f .env ] || cp "$SUPERSET_ROOT_PATH/.env" .env

set_env() {
  if grep -qE "^#? ?$1=" .env; then
    sed -i '' -E "s|^#? ?$1=.*|$1=$2|" .env
  else
    echo "$1=$2" >> .env
  fi
}
set_env APP_URL "https://$SITE.test"
set_env DB_CONNECTION sqlite
set_env DB_DATABASE "$PWD/database/database.sqlite"

# Vite looks for the Herd certificate under the directory name, not the site name, so point it at the site's certificate.
certificates="$HOME/Library/Application Support/Herd/config/valet/Certificates"
set_env VITE_DEV_SERVER_KEY "\"$certificates/$SITE.test.key\""
set_env VITE_DEV_SERVER_CERT "\"$certificates/$SITE.test.crt\""

# Its own SQLite database, seeded only when it is created.
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
  php artisan migrate --seed --force
else
  php artisan migrate --force
fi

php artisan wayfinder:generate --with-form
herd link "$SITE" --secure
