# Use PHP 8.2 with FPM
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libonig-dev \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql mbstring zip pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Laravel project
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create SQLite database file BEFORE setting permissions
RUN mkdir -p /var/www/database \
    && touch /var/www/database/database.sqlite \
    && chmod 666 /var/www/database/database.sqlite

# Set permissions for storage and cache
RUN chown -R www-data:www-data /var/www/storage \
    /var/www/bootstrap/cache \
    /var/www/database \
    && chmod -R 775 /var/www/storage \
    /var/www/bootstrap/cache \
    /var/www/database

# Generate Laravel key (if not set in env)
RUN php artisan key:generate --force

# Run migrations (using --force for production)
RUN php artisan migrate --force || echo "Migration failed, will run at runtime"

# Create startup script
RUN echo '#!/bin/sh\n\
php artisan migrate --force\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}\n\
' > /start.sh && chmod +x /start.sh

# Expose port (Render sets $PORT automatically)
EXPOSE 8000

# Use startup script
CMD ["/start.sh"]
