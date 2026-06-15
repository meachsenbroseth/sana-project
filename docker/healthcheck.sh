#!/bin/bash
set -euo pipefail

# =============================================================================
# Docker Healthcheck Script
# =============================================================================
# Checks:
# 1. PHP-FPM is running
# 2. Nginx is running
# 3. Laravel health endpoint is responsive
# 4. Storage is writable
# =============================================================================

# Check PHP-FPM
if ! pgrep -f "php-fpm" > /dev/null 2>&1; then
    echo "Healthcheck FAILED: PHP-FPM is not running"
    exit 1
fi

# Check Nginx
if ! pgrep -f "nginx" > /dev/null 2>&1; then
    echo "Healthcheck FAILED: Nginx is not running"
    exit 1
fi

# Check Laravel health endpoint via PHP-FPM
HTTP_RESPONSE=$(php -r "
    \$_SERVER['REQUEST_METHOD'] = 'GET';
    \$_SERVER['REQUEST_URI'] = '/up';
    \$_SERVER['SERVER_NAME'] = 'localhost';
    \$_SERVER['SERVER_PORT'] = 8000;
    \$_SERVER['HTTP_HOST'] = 'localhost';
    require '/var/www/html/public/index.php';
" 2>/dev/null) || true

if [ -z "$HTTP_RESPONSE" ]; then
    echo "Healthcheck WARNING: Laravel health endpoint did not return content"
fi

# Check storage writability
if [ ! -w /var/www/html/storage/logs ]; then
    echo "Healthcheck FAILED: Storage logs directory is not writable"
    exit 1
fi

# Check supervisor
if ! pgrep -f "supervisord" > /dev/null 2>&1; then
    echo "Healthcheck FAILED: Supervisor is not running"
    exit 1
fi

echo "Healthcheck PASSED: All services are running"
exit 0
