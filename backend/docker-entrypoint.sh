#!/bin/sh
set -e

# =============================================================================
# Backend container entrypoint
# Runs at container startup (NOT during docker build).
# =============================================================================

echo "=========================================="
echo "  Plant Collection — Backend Startup"
echo "=========================================="

# ── 1. APP_KEY guard ──────────────────────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
  echo "==> APP_KEY not set — generating a temporary key..."
  php artisan key:generate --force --ansi
  echo "    WARNING: Set APP_KEY in your .env (root) for a stable key across restarts."
else
  echo "==> APP_KEY is set v"
fi

# ── 2. Wait for MySQL TCP port to be ready ───────────────────────────────────
# Using raw TCP check (nc/netcat) to avoid bootstrapping Laravel before DB ready.
echo "==> Waiting for database (${DB_HOST:-db}:${DB_PORT:-3306})..."
RETRIES=40
until nc -z "${DB_HOST:-db}" "${DB_PORT:-3306}" 2>/dev/null; do
  RETRIES=$((RETRIES - 1))
  if [ "$RETRIES" -le 0 ]; then
    echo "  ERROR: Could not connect to database after multiple retries. Exiting."
    exit 1
  fi
  echo "  DB port not open yet — retrying in 2s... ($RETRIES retries left)"
  sleep 2
done
echo "==> Database TCP port open v"

# Extra wait for MySQL to finish initialization (accepting queries)
sleep 3

# ── 3. Clear any stale cache, then rebuild ────────────────────────────────────
echo "==> Clearing stale caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan event:clear

echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan event:cache

# ── 4. Run database migrations ───────────────────────────────────────────────
echo "==> Running migrations..."
php artisan migrate --force

# ── 5. Seed default user (idempotent — safe to run on every startup) ──────────
echo "==> Seeding default user..."
php artisan db:seed --force

echo "=========================================="
echo "  Starting FrankenPHP on :8000"
echo "=========================================="

# exec replaces the shell with frankenphp (PID 1) for clean signal handling
exec frankenphp php-server --listen :8000 --root public/
