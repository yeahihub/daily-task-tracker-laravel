# ==========================
# Stage 1 - Composer
# ==========================
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN composer dump-autoload --optimize

# ==========================
# Stage 2 - Node
# ==========================
FROM node:24 AS node

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build

# ==========================
# Stage 3 - Production
# ==========================
FROM php:8.4-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    git \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        zip\
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /app /var/www
COPY --from=node /app/public/build /var/www/public/build

RUN mkdir -p \
    storage/framework/cache \
    storage/framework/views \
    storage/framework/sessions \
    storage/logs \
    bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan config:clear && \
php artisan cache:clear && \
php artisan migrate --force && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
    php artisan serve \
        --host=0.0.0.0 \
        --port=${PORT:-10000}