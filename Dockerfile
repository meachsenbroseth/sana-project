# syntax=docker/dockerfile:1
# Multi-stage build for production PHP 8.4 + Laravel + Nginx + Supervisor

# ============================================================
# STAGE 1: Composer dependencies
# ============================================================
FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# ============================================================
# STAGE 2: Node assets (Vite build)
# ============================================================
FROM node:22-alpine AS assets

WORKDIR /app

RUN apk add --no-cache libc6-compat

COPY package.json package-lock.json ./

RUN npm ci --no-audit --no-fund && \
    npm cache clean --force

COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN npm run build

# ============================================================
# STAGE 3: Production image
# ============================================================
FROM php:8.4-fpm-bookworm AS production

ARG WWWGROUP=1001
ARG WWWUSER=1001

ENV APP_ENV=production \
    APP_DEBUG=false \
    DEBIAN_FRONTEND=noninteractive \
    PHP_OPCACHE_ENABLE=1 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    TZ=UTC

# System packages
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        ca-certificates \
        curl \
        gettext-base \
        git \
        unzip \
        zip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libpq-dev \
        libsqlite3-dev \
        libonig-dev \
        libxml2-dev \
        libwebp-dev \
        libxpm-dev \
        cron \
    && rm -rf /var/lib/apt/lists/* /var/cache/apt/archives/*

# PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-xpm \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        pdo_sqlite \
        pdo_mysql \
        xml \
        zip \
        exif

# Redis extension from PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Enable OPcache
RUN docker-php-ext-enable opcache

# Install composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Create application user with proper home
RUN groupadd --force -g ${WWWGROUP} app \
    && useradd -ms /bin/bash --no-user-group -g app -u ${WWWUSER} app \
    && mkdir -p /home/app/.composer \
    && chown -R app:app /home/app

# Application directory setup
WORKDIR /var/www/html

# Copy application source
COPY --chown=app:app . .

# Copy vendor from composer stage
COPY --chown=app:app --from=vendor /app/vendor ./vendor

# Copy built assets from node stage
COPY --chown=app:app --from=assets /app/public/build ./public/build

# Create required directories with proper permissions
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
        /var/log/php \
        /var/log/supervisor \
    && chown -R app:app storage bootstrap/cache /var/log/php /var/log/supervisor \
    && chmod -R 775 storage bootstrap/cache

# Install application dependencies with production optimizations
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-ansi

# PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/opcache-blacklist.txt /etc/php/opcache-blacklist.txt

# PHP-FPM configuration for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx-laravel.conf /etc/nginx/conf.d/laravel.conf

# Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor-laravel.conf /etc/supervisor/conf.d/laravel.conf

# Healthcheck script
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Cleanup
RUN apt-get clean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && docker-php-ext-enable opcache

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh

EXPOSE 8000

# Note: We run as root so supervisor can manage php-fpm/nginx.
# The app user (non-root) runs queue workers and scheduler for security.
# The PHP-FPM pool runs as 'app' user via www.conf configuration.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
