FROM php:8.3-fpm

# Install system dependencies including unzip and git for Composer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql

# Set working directory
WORKDIR /var/www

# Create necessary directories for Laravel before copying files
RUN mkdir -p bootstrap/cache storage/logs storage/framework/{cache,sessions,views} public/storage

# Copy application files
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies with optimizations
RUN composer install --optimize-autoloader --no-dev

# Fix broken symlink storage and create proper storage directories
RUN rm -f public/storage && mkdir -p public/storage
RUN mkdir -p storage/app/public && chown -R www-data:www-data storage

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/bootstrap/cache

EXPOSE 80

CMD php artisan serve --host=0.0.0.0 --port=80