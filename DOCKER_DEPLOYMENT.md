# Production Deployment Guide

## Overview

This deployment setup uses Docker with a multi-stage build optimized for Laravel 12 with Filament Admin on PostgreSQL. It is designed to run on Railway or any Docker-compatible platform.

## Architecture

```
Nginx (port $PORT)  →  PHP-FPM (unix socket)  →  Laravel 12
                         ↑
                    Supervisor
                    ├── php-fpm
                    ├── nginx
                    ├── queue:work (x2, short jobs)
                    ├── queue:work (x1, long-running)
                    └── schedule:work
```

## Required Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Laravel app key (32-byte base64) | `base64:...` |
| `APP_ENV` | Must be `production` | `production` |
| `APP_DEBUG` | Must be `false` | `false` |
| `APP_URL` | Public application URL | `https://yourapp.railway.app` |
| `DB_CONNECTION` | Database driver | `pgsql` |
| `DB_HOST` | Database host | `your-db.railway.app` |
| `DB_PORT` | Database port | `5432` |
| `DB_DATABASE` | Database name | `railway` |
| `DB_USERNAME` | Database user | `postgres` |
| `DB_PASSWORD` | Database password | (use Railway secret) |
| `SESSION_DRIVER` | Session driver | `database` |

## Optional Variables

| Variable | Default | Recommendation |
|----------|---------|----------------|
| `LOG_CHANNEL` | `stack` | Use `stderr` for Docker, or `daily` |
| `LOG_LEVEL` | `debug` | Set to `error` in production |
| `CACHE_STORE` | `database` | Use `redis` if available |
| `QUEUE_CONNECTION` | `database` | Use `redis` if available |
| `SESSION_SECURE_COOKIE` | `false` | Set `true` for HTTPS |
| `FILESYSTEM_DISK` | `local` | Use `s3` for distributed setups |
| `SCOUT_DRIVER` | `collection` | Use `meilisearch` or `typesense` |

## Railway-Specific Variables

| Variable | Description |
|----------|-------------|
| `PORT` | Railway's assigned port (auto-detected by Nginx) |
| `RAILWAY_SERVICE_ID` | Railway internal service ID |
| `RAILWAY_SERVICE_NAME` | Railway service name |
| `RAILWAY_ENVIRONMENT` | Railway environment name |
| `RAILWAY_PROJECT_ID` | Railway project ID |
| `RAILWAY_PROJECT_NAME` | Railway project name |

## Security Recommendations

1. **APP_KEY**: Generate with `php artisan key:generate --show`, store in Railway secrets
2. **APP_DEBUG**: Always `false` in production
3. **APP_ENV**: Always `production` in production
4. **Session cookies**: Set `SESSION_SECURE_COOKIE=true` (requires HTTPS)
5. **Database**: Use Railway's built-in PostgreSQL with SSL; set `DB_SSLMODE=require`
6. **Queue**: Prefer `redis` over `database` for better performance
7. **Cache**: Prefer `redis` over `database` for better performance
8. **Rate limiting**: Already configured in Fortify (5 requests/minute per login)

## Railway Deployment

### 1. Add PostgreSQL Plugin

```bash
railway add postgres
```

### 2. Set Environment Variables

```bash
railway variables set APP_KEY=$(php artisan key:generate --show)
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set APP_URL=https://your-service.up.railway.app
railway variables set SESSION_SECURE_COOKIE=true
railway variables set DB_SSLMODE=require
```

### 3. Deploy

```bash
railway up
```

Or connect your GitHub repo and Railway will auto-deploy.

### 4. First-time Setup (one-time)

After the first deploy, run these once in a Railway shell:

```bash
railway run php artisan storage:link
railway run php artisan shield:install  # If using Filament Shield
railway run php artisan shield:super-admin  # Create initial admin
```

## Build Process (Docker Multi-Stage)

```
Stage 1: composer ──► vendor/ (composer install --no-dev)
Stage 2: node     ──► public/build/ (npm ci + npm run build)
Stage 3: php:8.4  ──► Final production image (all layers combined)
```

### Optimization Details

- **Classmap authoritative autoloading**: `--classmap-authoritative` for faster class loading
- **OPcache JIT**: Enabled with 100MB buffer for PHP 8.4 JIT compilation
- **Composer optimize**: `--optimize-autoloader --classmap-authoritative`
- **Nginx fastcgi buffering**: 16×16k buffers for better throughput
- **PHP-FPM unix socket**: Faster than TCP loopback
- **Gzip**: Enabled for text-based assets
- **No dev dependencies**: `--no-dev` flags ensure minimal image

## Production Optimizations Performed on Boot

The entrypoint script runs these in order:

1. **Environment validation** — Ensures all required variables are set
2. **Database readiness check** — Waits for PostgreSQL to accept connections
3. **Storage setup** — Creates writable storage directories
4. **Storage link** — Creates `public/storage` → `storage/app/public` symlink
5. **Migrations** — Runs `php artisan migrate --force`
6. **Config cache** — `php artisan config:cache` (merges all configs)
7. **Route cache** — `php artisan route:cache` (compiles routes)
8. **Event cache** — `php artisan event:cache` (optimizes event discovery)
9. **View cache** — `php artisan view:cache` (compiles Blade templates)
10. **OPcache dir** — Creates OPcache file cache for persistence across restarts
11. **Verification** — Confirms autoloader, assets, and artisan availability

## Supervisor-Managed Processes

| Process | Priority | Count | Purpose |
|---------|----------|-------|---------|
| php-fpm | 10 | 1 | PHP FastCGI Process Manager |
| nginx | 20 | 1 | Web server |
| queue-worker | 30 | 2 | Default + notifications + embeddings jobs |
| queue-worker-long | 31 | 1 | Long-running jobs (e.g., product embedding generation) |
| scheduler | 40 | 1 | Laravel scheduled tasks |

## Health Checks

The container healthcheck runs every 30 seconds (60s startup grace period) and verifies:

- PHP-FPM process is running
- Nginx process is running
- Laravel `/up` health endpoint responds
- Storage logs directory is writable
- Supervisor is running

## Persistent Storage

The `storage/` directory must be persisted across deployments. On Railway, use:

- **Railway Volumes** — Mount a volume at `/var/www/html/storage`
- **S3** — Set `FILESYSTEM_DISK=s3` with proper AWS credentials
- **Local ephemeral** — Works but data lost on redeploy (ok for sessions/cache)

### Files to persist:
- `storage/app/public/` — User uploads
- `storage/logs/` — Application logs
- `storage/framework/sessions/` — Active sessions (if using file driver)

### Files that can be ephemeral:
- `storage/framework/cache/` — Rebuilds on startup
- `storage/framework/views/` — Rebuilds on startup
- `bootstrap/cache/` — Rebuilds on startup

## Troubleshooting

### 500 Internal Server Error
```bash
# Check Nginx error logs
cat /var/log/nginx/error.log
# Check Laravel logs
cat /var/www/html/storage/logs/laravel.log
# Check PHP-FPM logs
cat /var/log/supervisor/php-fpm.log
```

### Vite Assets Not Loading
```bash
# Verify build directory exists
ls -la /var/www/html/public/build/
# If missing, rebuild with:
npm ci && npm run build
```

### Queue Jobs Not Processing
```bash
# Check queue worker status
supervisorctl status queue-worker:*
# Restart workers
supervisorctl restart queue-worker:*
# Check failed jobs table
php artisan queue:failed
```

### Database Connection Issues
```bash
# Test connection
php artisan db:monitor
# Check connection config
php artisan config:show database
```

## Local Testing

```bash
# Build the image
docker build -t phanna-computer:latest .

# Run locally
docker run -it --rm \
    -p 8000:8000 \
    -e APP_KEY=base64:... \
    -e APP_ENV=production \
    -e APP_URL=http://localhost:8000 \
    -e DB_CONNECTION=pgsql \
    -e DB_HOST=host.docker.internal \
    -e DB_PORT=5432 \
    -e DB_DATABASE=phannacomputer-data \
    -e DB_USERNAME=postgres \
    -e DB_PASSWORD=password \
    -e APP_DEBUG=false \
    phanna-computer:latest
```
