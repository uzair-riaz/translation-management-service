FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Install essential dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install only required PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Expose port 8000 for the Laravel development server
EXPOSE 8000

# Start Laravel server
CMD bash -c "composer install && php artisan key:generate --force && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"
