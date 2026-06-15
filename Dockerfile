# syntax=docker/dockerfile:1
FROM php:8.4-fpm-alpine AS base

# --- Dépendances système + extensions PHP (pas de pdo_mysql : aucune base de données) ---
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        zip \
        opcache

# Configuration OPcache pour la prod
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- Dépendances PHP (cache de couches tant que les lockfiles ne changent pas) ---
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-progress --no-interaction --prefer-dist

# --- Code applicatif ---
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# --- Configs Nginx / Supervisor / entrypoint ---
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# var/ doit être inscriptible par php-fpm (www-data)
RUN mkdir -p var && chown -R www-data:www-data var

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
