#!/bin/sh
set -e

# Fix permissions for mounted volumes in development
if [ -d "/var/www/html" ]; then
    # Make everything readable/executable by www-data
    find /var/www/html -type d -exec chmod 755 {} \; 2>/dev/null || true
    find /var/www/html -type f -exec chmod 644 {} \; 2>/dev/null || true
    
    # Ensure var directory is writable
    if [ -d "/var/www/html/var" ]; then
        chown -R www-data:www-data /var/www/html/var 2>/dev/null || true
        chmod -R 775 /var/www/html/var 2>/dev/null || true
    fi
fi

# Execute the main command
exec "$@"
