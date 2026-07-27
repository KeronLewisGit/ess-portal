#!/bin/sh
set -e

# Local development only: Windows bind mounts surface as root-owned, so make
# Laravel's writable directories accessible to the php-fpm (www-data) user on
# every container start. Never do this in production.
if [ -d /var/www/html/storage ]; then
    chmod -R ugo+rwX /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

exec "$@"
