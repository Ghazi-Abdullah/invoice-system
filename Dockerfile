FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libgd-dev libpng-dev libjpeg-dev \
    libzip-dev zip unzip git curl \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring zip bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --optimize-autoloader --no-dev --no-interaction

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}


