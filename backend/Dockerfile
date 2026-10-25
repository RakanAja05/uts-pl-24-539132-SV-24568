# =============================================================================
# Backend Dockerfile — FrankenPHP (Nilai Tambahan +10)
# Base: dunglas/frankenphp:1-php8.3-alpine
# =============================================================================

FROM dunglas/frankenphp:1-php8.4-alpine

LABEL maintainer="Plant Collection API"
LABEL description="Laravel 13 API served by FrankenPHP (PHP 8.4)"

WORKDIR /app

# ── System dependencies ───────────────────────────────────────────────────────
RUN apk add --no-cache \
        git \
        curl \
        unzip \
        bash \
        netcat-openbsd

# ── PHP extensions ────────────────────────────────────────────────────────────
# install-php-extensions is pre-bundled in the FrankenPHP image
RUN install-php-extensions \
        pdo_mysql \
        intl \
        zip \
        opcache \
        pcntl \
        bcmath

# ── Composer (copy binary from official Composer image) ───────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Layer cache: dependency files FIRST (before app code) ─────────────────────
# This ensures "composer install" is re-run ONLY when composer.json/lock changes,
# not on every source-code change.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-scripts \
        --no-interaction \
        --prefer-dist

# ── Application code ──────────────────────────────────────────────────────────
COPY . .

# Create a blank .env so artisan doesn't throw "file not found" errors;
# real config comes from Docker environment variables at runtime.
RUN touch .env

# Storage & cache directories must be writable by the web server process
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app/storage /app/bootstrap/cache

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
