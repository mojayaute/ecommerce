#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    APP_KEY="$(php artisan key:generate --show --no-ansi)"
    export APP_KEY
    echo "APP_KEY not provided: generated an ephemeral key for this container." >&2
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan config:cache --no-ansi
php artisan route:cache --no-ansi

exec "$@"
