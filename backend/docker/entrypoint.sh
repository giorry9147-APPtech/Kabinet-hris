#!/bin/sh
set -e

APP_DIR="/var/www/html"
cd "$APP_DIR"

# Fly.io attaches a Postgres app via DATABASE_URL. Laravel reads DB_URL.
if [ -n "$DATABASE_URL" ] && [ -z "$DB_URL" ]; then
    export DB_URL="$DATABASE_URL"
fi

# Ensure runtime-writable dirs exist (volumes start empty)
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# storage:link is idempotent; --force replaces a stale link
php artisan storage:link --force >/dev/null 2>&1 || true

# Wait for Postgres before running migrations
if [ -n "$DB_URL" ]; then
    DB_HOST_PARSED=$(printf '%s' "$DB_URL" | sed -E 's|^[^@]+@([^:/?]+).*|\1|')
    DB_PORT_PARSED=$(printf '%s' "$DB_URL" | sed -nE 's|^[^@]+@[^:]+:([0-9]+).*|\1|p')
    DB_HOST_PARSED=${DB_HOST_PARSED:-${DB_HOST:-127.0.0.1}}
    DB_PORT_PARSED=${DB_PORT_PARSED:-${DB_PORT:-5432}}
elif [ -n "$DB_HOST" ]; then
    DB_HOST_PARSED="$DB_HOST"
    DB_PORT_PARSED="${DB_PORT:-5432}"
fi

if [ -n "$DB_HOST_PARSED" ]; then
    echo "[entrypoint] Waiting for Postgres at $DB_HOST_PARSED:$DB_PORT_PARSED ..."
    i=0
    until pg_isready -h "$DB_HOST_PARSED" -p "$DB_PORT_PARSED" -q; do
        i=$((i+1))
        if [ "$i" -gt 30 ]; then
            echo "[entrypoint] Postgres still not ready after 60s, continuing anyway"
            break
        fi
        sleep 2
    done
fi

# Package discovery — composer install ran with --no-scripts, so we register
# auto-discovered providers (Filament etc.) here, where env is fully populated.
echo "[entrypoint] Discovering packages ..."
php artisan package:discover --ansi
php artisan filament:assets --ansi 2>/dev/null || true

# Migrations (always safe — only applies what's pending)
echo "[entrypoint] Running migrations ..."
php artisan migrate --force --no-interaction

# Conditional seed: only if no users exist yet
USER_COUNT=$(php artisan tinker --execute "echo \\App\\Models\\User::count();" 2>/dev/null | tail -n1 | tr -d '[:space:]')
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    if [ "${SEED_ON_EMPTY:-true}" = "true" ]; then
        echo "[entrypoint] Empty database detected — seeding demo data ..."
        php artisan db:seed --force --no-interaction || true
    fi
fi

# Cache config/routes/views (env is now finalised)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Filament needs its component cache
php artisan icons:cache  >/dev/null 2>&1 || true

echo "[entrypoint] Boot complete — handing off to: $*"
exec "$@"
