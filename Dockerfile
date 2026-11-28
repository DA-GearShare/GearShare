# Two targets, picked by the compose files:
#   dev   compose.override.yaml (plain `docker compose up`). Code is bind-mounted.
#   prod  compose.prod.yaml. Code, vendor/ (no dev deps) and a warm cache are baked in.
# `docker build .` without --target builds the last stage (dev).

FROM php:8.5-apache AS base

RUN apt-get update -y && apt-get install -y openssl zip unzip zlib1g-dev libpq-dev libicu-dev libzip-dev curl libpng-dev git \
	&& rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer
RUN docker-php-ext-install pdo pdo_pgsql zip gd exif pcntl

COPY httpd-vhosts.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

RUN echo "max_execution_time = 1200" > /usr/local/etc/php/conf.d/execution.ini
RUN echo "memory_limit = 2048M" >> /usr/local/etc/php/conf.d/execution.ini
RUN echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/execution.ini
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/execution.ini
# Without "E", PHP under Apache never sees the container's environment variables
# and Symfony silently falls back to the values in .env.
RUN echo "variables_order = EGPCS" > /usr/local/etc/php/conf.d/env.ini

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www/html/

COPY docker-entrypoint.sh /usr/local/bin/
# runs messenger:consume instead of Apache
COPY worker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/worker-entrypoint.sh
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]


FROM base AS prod

ENV APP_ENV=prod \
	APP_DEBUG=0

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN { \
		echo "opcache.enable=1"; \
		echo "opcache.validate_timestamps=0"; \
		echo "opcache.memory_consumption=256"; \
		echo "opcache.max_accelerated_files=20000"; \
		echo "opcache.preload=/var/www/html/config/preload.php"; \
		echo "opcache.preload_user=www-data"; \
		echo "realpath_cache_size=4096K"; \
		echo "realpath_cache_ttl=600"; \
	} > /usr/local/etc/php/conf.d/prod.ini

# Dependencies first, so code changes don't invalidate this layer.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

COPY . .

# .env.local.php: compiled .env for prod (real environment variables still win),
# then cache:clear + assets:install via the composer auto-scripts.
RUN composer dump-autoload --no-dev --classmap-authoritative \
	&& composer dump-env prod \
	&& composer run-script --no-dev post-install-cmd \
	&& chown -R www-data:www-data var


FROM base AS dev

RUN apt-get update -y && apt-get install -y nano cron inetutils-ping sqlite3 libsqlite3-dev \
	&& rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

# vendor/ is not part of the build context (.dockerignore), so install it here.
RUN if [ ! -d vendor ]; then composer install --no-interaction; fi

# Cache vendor so the entrypoint can restore it after a volume mount
RUN cp -a /var/www/html/vendor /var/www/vendor-cache
