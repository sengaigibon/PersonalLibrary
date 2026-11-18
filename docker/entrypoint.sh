#!/bin/sh
set -e

# Fix permissions for mounted volumes in development
if [ -d "/var/www/html" ]; then
    # Make everything readable/executable by www-data
    find /var/www/html -type d -exec chmod 755 {} \; 2>/dev/null || true
    find /var/www/html -type f -exec chmod 644 {} \; 2>/dev/null || true
    
fi

# Symfony cache and logs live inside the container (APP_CACHE_DIR / APP_LOG_DIR in compose.yaml),
# not in the bind-mounted ./var, so the host keeps ownership of ./var.
mkdir -p "${APP_CACHE_DIR:-/tmp/symfony/cache}" "${APP_LOG_DIR:-/tmp/symfony/log}"
chown -R www-data:www-data "${APP_CACHE_DIR:-/tmp/symfony/cache}" "${APP_LOG_DIR:-/tmp/symfony/log}"

# Execute the main command
exec "$@"
