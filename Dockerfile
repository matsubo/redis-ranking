# Development image: PHP CLI + phpredis + Composer. compose.yaml builds it
# for each supported PHP version.
ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-cli

ARG PHPREDIS_VERSION=6.3.0
RUN apt-get update -q && apt-get install -yq --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/* \
 && pecl install redis-${PHPREDIS_VERSION} \
 && docker-php-ext-enable redis \
 && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_ROOT_VERSION=2.0.0
# The repo is bind-mounted with the host's owner.
RUN git config --global --add safe.directory /app
