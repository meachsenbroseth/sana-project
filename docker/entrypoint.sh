#!/bin/bash
set -euo pipefail

trap 'echo "Entrypoint interrupted. Exiting."; exit 0' INT TERM

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
log()     { echo -e "${BLUE}[DEPLOY]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }
fail()    { echo -e "${RED}[FAIL]${NC} $1"; exit 1; }

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
    log "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."

    retries=60
    until php -r "
        try {
            new PDO(
                'pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
                '${DB_USERNAME}',
                '${DB_PASSWORD}',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            exit(0);
        } catch (PDOException \$e) {
            fwrite(STDERR, \$e->getMessage() . PHP_EOL);
            exit(1);
        }
    "; do
        retries=$((retries - 1))
        if [ "$retries" -le 0 ]; then
            fail "PostgreSQL did not become available after 60 attempts"
        fi
        log "Retrying... (${retries} attempts remaining)"
        sleep 2
    done
    success "PostgreSQL is ready"
fi

# =============================================================================
# STEP 3: Storage directories and permissions
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
# STEP 4: Storage symlink
# =============================================================================
log "Creating storage symlink..."
if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction --force 2>/dev/null || \
        ln -sf ../storage/app/public public/storage
fi
success "Storage symlink ready"

# =============================================================================
# STEP 5: Migrations
# =============================================================================
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "Running database migrations..."
    php artisan migrate --force --no-interaction 2>&1 || warn "Migration encountered issues"
    success "Migrations complete"
fi

# =============================================================================
# STEP 6: Cache for production
# =============================================================================
log "Caching Laravel configurations..."
php artisan optimize:clear --no-interaction 2>/dev/null || true
php artisan config:cache --no-interaction && success "Config cached"   || warn "Config cache failed"
php artisan route:cache  --no-interaction && success "Routes cached"   || warn "Route cache failed"
php artisan event:cache  --no-interaction && success "Events cached"   || warn "Event cache failed"
php artisan view:cache   --no-interaction && success "Views cached"    || warn "View cache failed"

# =============================================================================
# STEP 7: OPcache file-cache directory
# =============================================================================
log "Setting up OPcache..."
mkdir -p /tmp/opcache && chmod 777 /tmp/opcache
success "OPcache directory ready"

# =============================================================================
# STEP 8: Sanity checks
# =============================================================================
log "Verifying application..."
[ -f vendor/autoload.php ] || fail "Composer autoloader not found"
[ -d public/build ]        || warn "Vite build directory not found — run npm run build"
php artisan about --no-interaction 2>/dev/null | head -20 || warn "Could not display application info"
success "Application verified"

# Optional: regenerate Filament Shield permissions
if [ "${FILAMENT_SHIELD_GENERATE:-false}" = "true" ]; then
    php artisan shield:generate --all --no-interaction || true
fi

# =============================================================================
# STEP 9: Configure Nginx port (Railway injects $PORT at runtime)
# =============================================================================
log "Configuring Nginx..."
PORT="${PORT:-8000}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/laravel.conf
success "Nginx configured on port ${PORT}"

# =============================================================================
# STEP 10: Start Supervisor
# =============================================================================
log "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
