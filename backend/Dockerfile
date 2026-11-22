FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql zip bcmath gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && php artisan package:discover --ansi || true

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

ENV PORT=8000
EXPOSE 8000

CMD ["start.sh"]
