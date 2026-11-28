#!/bin/bash
set -e

# Long-running Messenger worker: sends the mails queued in the "email" transport.
# Migrations and fixtures are the app container's job; the worker only starts
# once app is healthy.

if [ ! -d /var/www/html/vendor ] || [ -z "$(ls -A /var/www/html/vendor 2>/dev/null)" ]; then
	echo "==> Restoring vendor/ from build cache..."
	mkdir -p /var/www/html/vendor
	cp -a /var/www/vendor-cache/. /var/www/html/vendor/
fi

mkdir -p /var/www/html/var/cache /var/www/html/var/log
chown -R www-data:www-data /var/www/html/var

# --time-limit / --memory-limit: the worker exits periodically and Compose
# (restart: unless-stopped) starts a fresh one, which avoids memory leaks and
# picks up new code. SIGTERM (docker compose stop) is handled gracefully via
# pcntl: the current message is finished before exiting.
echo "==> Starting messenger worker (queue: email)..."
exec runuser -u www-data -- php /var/www/html/bin/console messenger:consume email \
	--time-limit=3600 --memory-limit=128M -vv
