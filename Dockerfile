# JPMS Central-Service — the only layer with Postgres/MongoDB/Redis/disk
# credentials. Two-container run (this image is the php-fpm side; nginx.conf
# in this same folder is used by the sibling nginx container in
# docker-compose.yml).

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# --ignore-platform-reqs: this builder stage doesn't have the pdo_pgsql/
# mongodb PHP extensions installed (those go in the runtime stage below) —
# Composer's own platform check would otherwise fail even though the
# extensions ARE present by the time the code actually runs.
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql bcmath zip intl opcache \
    && pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY docker/php.ini /usr/local/etc/php/conf.d/99-jpms.ini
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

# Stays root (php-fpm's own standard pattern — it drops worker processes
# to www-data internally via its pool config, see php-fpm.d/www.conf) so
# the entrypoint can write to the public-shared named volume, which Docker
# always creates owned by root regardless of which user mounts it.
EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
