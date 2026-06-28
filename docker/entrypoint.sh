#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

if grep -qE '^APP_KEY=$' .env; then
    php artisan key:generate --force --no-interaction
fi

exec "$@"
