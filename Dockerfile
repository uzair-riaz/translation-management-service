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

# Install Redis PHP extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Xdebug for code coverage
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configure Xdebug for code coverage
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Expose port 8000 for the Laravel development server
EXPOSE 8000

# Start Laravel server
CMD bash -c "composer install && \
    php artisan key:generate --force && \
    php artisan migrate --force && \
    php artisan optimize && \
    php artisan serve --host=0.0.0.0 --port=8000"
