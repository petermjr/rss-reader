FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    netcat-traditional

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Clean up
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Set permissions for specific directories only
RUN chown -R www-data:www-data /var/www/app \
    && chown -R www-data:www-data /var/www/config \
    && chown -R www-data:www-data /var/www/public \
    && chown -R www-data:www-data /var/www/resources \
    && chown -R www-data:www-data /var/www/vendor \
    && chown -R www-data:www-data /var/www/composer.json \
    && chown -R www-data:www-data /var/www/composer.lock

# Copy entrypoint script
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 8080
EXPOSE 8080

CMD ["php-fpm"] 