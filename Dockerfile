FROM php:8.3-fpm

# Install system dependencies required for building extensions and composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql zip

COPY . /var/www

WORKDIR /var/www

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies defined in composer.lock
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

RUN mkdir -p /var/www/storage/logs && chown -R www-data:www-data /var/www/storage

EXPOSE 9000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]
