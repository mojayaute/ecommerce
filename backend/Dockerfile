FROM dunglas/frankenphp:1.13-php8.5-alpine AS base

RUN install-php-extensions pdo_pgsql bcmath intl opcache pcntl zip

COPY --from=composer:2.11 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app


FROM base AS dev

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN cp .env.example .env \
    && composer dump-autoload --optimize \
    && php artisan package:discover --ansi

CMD ["php", "artisan", "test"]


FROM base AS prod

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache /config/caddy /data/caddy

COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint

USER www-data

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
