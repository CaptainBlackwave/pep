#!/bin/bash
set -e

# Generate .env if missing
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env 2>/dev/null || touch .env
fi

# Generate app key if not set
php artisan key:generate --force --ansi 2>/dev/null || true

# ── Auto-install on first boot ──────────────────────────
# Waits for MySQL, then runs the unified installer
if [ ! -f "storage/installed" ]; then
    echo "First boot detected — waiting for database..."
    MAX_TRIES=30
    COUNT=0
    until php artisan migrate:status &>/dev/null || [ $COUNT -ge $MAX_TRIES ]; do
        echo "  Waiting for database... ($((COUNT+1))/${MAX_TRIES})"
        sleep 2
        COUNT=$((COUNT+1))
    done

    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "⚠ Database not ready after ${MAX_TRIES} attempts — skipping auto-install"
    else
        echo "Database ready — running installer..."
        php artisan app:install --force \
            --admin-username="${ADMIN_USERNAME:-admin}" \
            --admin-email="${ADMIN_EMAIL:-admin@example.com}" \
            --admin-password="${ADMIN_PASSWORD:-admin123}"
    fi
fi

# Cache config for performance (skip in dev if APP_ENV=local)
if [ "$APP_ENV" != "local" ]; then
    php artisan config:cache --ansi 2>/dev/null || true
    php artisan route:cache --ansi 2>/dev/null || true
    php artisan view:cache --ansi 2>/dev/null || true
fi

# Run IDE helper in dev (suppresses the 34 IDE warnings)
if [ "$APP_ENV" = "local" ] && command -v php &>/dev/null; then
    php artisan ide-helper:generate 2>/dev/null || true
    php artisan ide-helper:meta 2>/dev/null || true
fi

exec "$@"
