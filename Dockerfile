FROM php:8.3-cli AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libonig-dev \
        libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite mbstring bcmath zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

COPY IntegrationService/composer.json IntegrationService/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY IntegrationService/ ./

COPY erpXpto/ /var/www/erpXpto/

RUN composer dump-autoload --optimize --no-scripts

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "list"]
