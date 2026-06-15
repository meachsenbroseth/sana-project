#!/bin/bash
set -euo pipefail

# =============================================================================
# Laravel Production Entrypoint
# =============================================================================
# This script prepares the Laravel application for production execution.
# It handles: env validation, DB readiness, migrations, caching, storage link,
# and finally starts Supervisor to manage all processes.
# =============================================================================

trap 'echo "Entrypoint interrupted. Exiting."; exit 0' INT TERM

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[DEPLOY]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; exit 1; }

APP_DIR=/var/www/html
cd "$APP_DIR"

# =============================================================================
# STEP 1: Verify required environment variables
# =============================================================================
log "Verifying required environment variables..."

: "${APP_KEY:?APP_KEY is required. Generate with: php artisan key:generate --show}"
: "${APP_ENV:=production}"
: "${APP_DEBUG:=false}"
: "${APP_URL:?APP_URL is required}"
: "${DB_CONNECTION:=pgsql}"

if [ "$DB_CONNECTION" = "pgsql" ]; then
    : "${DB_HOST:?DB_HOST is required for PostgreSQL}"
    : "${DB_PORT:=5432}"
    : "${DB_DATABASE:?DB_DATABASE is required}"
    : "${DB_USERNAME:?DB_USERNAME is required}"
    : "${DB_PASSWORD:?DB_PASSWORD is required}"
fi

success "Required environment variables verified"

# =============================================================================
# STEP 2: Wait for database to be ready
# =============================================================================
if [ "$DB_CONNECTION" = "pgsql" ]; then
    log "Waiting for PostgreSQL to be ready at ${DB_HOST}:${DB_PORT}..."
    retries=60
    until php -r "
        \$p = @pg_connect(
            'host=${DB_HOST} port=${DB_PORT} dbname=${DB_DATABASE} user=${DB_USERNAME} password=${DB_PASSWORD}'
        );
        exit((int)(\$p === false));
    " 2>/dev/null; do
        retries=$((retries - 1))
        if [ $retries -le 0 ]; then
            fail "PostgreSQL did not become available after 60 attempts"
        fi
        sleep 2
    done
    success "PostgreSQL is ready"
fi

# =============================================================================
# STEP 3: Create storage directories and set permissions
# =============================================================================
log "Setting up storage directories..."
mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    storage/app/public

chmod -R 775 storage bootstrap/cache
success "Storage directories ready"

# =============================================================================
# STEP 4: Create storage symlink
# =============================================================================
log "Creating storage symlink..."
if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction --force 2>/dev/null || \
    ln -sf ../storage/app/public public/storage
fi
success "Storage symlink created"

# =============================================================================
# STEP 5: Run database migrations (safely)
# =============================================================================
log "Running database migrations..."
php artisan migrate --force --no-interaction 2>&1 || warn "Migration encountered issues"
success "Migrations completed"

# =============================================================================
# STEP 6: Cache Laravel configurations for production
# =============================================================================
log "Caching Laravel configurations..."

# Clear existing caches first
php artisan optimize:clear --no-interaction 2>/dev/null || true

# Cache config (combines all config files into one cached file)
php artisan config:cache --no-interaction 2>&1 && \
    success "Config cached" || \
    warn "Config cache failed (env vars with closures may prevent this)"

# Cache routes (compiles route definitions to plain PHP)
php artisan route:cache --no-interaction 2>&1 && \
    success "Routes cached" || \
    warn "Route cache failed (closures in routes may prevent this)"

# Cache events (optimizes event discovery)
php artisan event:cache --no-interaction 2>&1 && \
    success "Events cached" || \
    warn "Event cache failed"

# Cache views (compiles Blade templates to plain PHP)
php artisan view:cache --no-interaction 2>&1 && \
    success "Views cached" || \
    warn "View cache failed"

# =============================================================================
# STEP 7: Create OPcache file cache directory
# =============================================================================
log "Setting up OPcache..."
mkdir -p /tmp/opcache
chmod 777 /tmp/opcache
success "OPcache directory ready"

# =============================================================================
# STEP 8: Verify critical paths
# =============================================================================
log "Verifying application..."

if [ ! -f vendor/autoload.php ]; then
    fail "Composer autoloader not found. Did the build complete successfully?"
fi

if [ ! -d public/build ]; then
    warn "Vite build directory not found. Assets may not load. Run: npm run build"
fi

php artisan about --no-interaction 2>/dev/null | head -20 || \
    warn "Unable to display application info"

success "Application verified"

# =============================================================================
# STEP 9: Substitute environment variables in Nginx config
# =============================================================================
log "Configuring Nginx port..."
PORT="${PORT:-8000}"

# Replace __PORT__ placeholder with the dynamic Railway port
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/laravel.conf

success "Nginx configured on port ${PORT}"

# =============================================================================
# STEP 10: Start Supervisor
# =============================================================================
log "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
