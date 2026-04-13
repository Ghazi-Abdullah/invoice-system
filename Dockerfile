FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libfreetype6-dev libpng-dev libjpeg62-turbo-dev \
    libzip-dev zip unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mbstring zip bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}


