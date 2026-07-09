#!/usr/bin/env bash
set -euo pipefail

required_vars=(
    APP_KEY
    APP_URL
    DB_CONNECTION
    DB_HOST
    DB_PORT
    DB_DATABASE
    DB_USERNAME
    DB_PASSWORD
)

missing_vars=()

for var_name in "${required_vars[@]}"; do
    if [[ -z "${!var_name:-}" ]]; then
        missing_vars+=("${var_name}")
    fi
done

if (( ${#missing_vars[@]} > 0 )); then
    printf 'Missing required environment variables: %s\n' "${missing_vars[*]}" >&2
    exit 1
fi

if [[ "${DB_CONNECTION}" != "pgsql" ]]; then
    printf 'DB_CONNECTION must be pgsql for this Railway image.\n' >&2
    exit 1
fi

run_as_app() {
    runuser --user app -- "$@"
}

for attempt in $(seq 1 60); do
    if php -r '
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s",
            getenv("DB_HOST"),
            getenv("DB_PORT"),
            getenv("DB_DATABASE")
        );

        new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    ' >/dev/null 2>&1; then
        break
    fi

    if [[ "${attempt}" -eq 60 ]]; then
        printf 'PostgreSQL was not reachable after 120 seconds.\n' >&2
        exit 1
    fi

    printf 'Waiting for PostgreSQL (%s/60)...\n' "${attempt}"
    sleep 2
done

mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    bootstrap/cache

chown -R app:app storage bootstrap/cache

run_as_app php artisan storage:link --force

if [[ "${RUN_MIGRATIONS:-true}" != "false" ]]; then
    run_as_app php artisan migrate --force
fi

run_as_app php artisan optimize

runtime_port="${PORT:-8000}"
sed -i "s/__PORT__/${runtime_port}/g" /etc/nginx/conf.d/laravel.conf

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
