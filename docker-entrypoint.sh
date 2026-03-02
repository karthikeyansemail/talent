#!/bin/bash
# =============================================================================
# Nalam Pulse — Docker entrypoint (production)
# Runs inside the nalampulse/app container on every startup.
# Waits for DB, runs migrations, caches config, then starts Apache.
# =============================================================================
set -e

echo "[entrypoint] Starting Nalam Pulse..."

# ── Wait for MySQL to accept connections ──────────────────────────────────────
echo "[entrypoint] Waiting for database (${DB_HOST}:${DB_PORT:-3306})..."
TRIES=0
until php -r "
    try {
        new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD'),
            [PDO::ATTR_TIMEOUT => 3]
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    TRIES=$((TRIES+1))
    if [ $TRIES -ge 30 ]; then
        echo "[entrypoint] ERROR: Database not reachable after 90 seconds. Check DB_HOST/DB_PASSWORD."
        exit 1
    fi
    echo "[entrypoint]   Not ready yet, retrying in 3s... (${TRIES}/30)"
    sleep 3
done
echo "[entrypoint] Database connected."

# ── Generate APP_KEY if not set ───────────────────────────────────────────────
if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force
fi

# ── Run migrations ─────────────────────────────────────────────────────────────
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# ── Storage symlink ────────────────────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ── Cache config/routes/views for performance ─────────────────────────────────
echo "[entrypoint] Caching config + routes + views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] Ready. Starting Apache..."

# ── Start Apache in foreground ─────────────────────────────────────────────────
exec apache2-foreground
