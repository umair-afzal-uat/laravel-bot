# Use a multi-stage build to keep the final image size smaller
# Start from the official PHP 8.0 image
FROM php:8.0-fpm AS builder

# Set the working directory
WORKDIR /var/www/html

# Update and upgrade before installing packages
RUN apt-get update && \
    apt-get upgrade -y && \
    # Install necessary packages with specific versions to avoid compatibility issues
    apt-get install -y \
        libc6-dev \
        libpng-dev=1.6.37-2ubuntu1.2 \
        libjpeg62-turbo-dev=1:1.5.90-0ubuntu0.18.04.1 \
        libfreetype6-dev=2.10.4-2ubuntu1.2 \
        locales \
        zip \
        unzip \
        git \
        curl \
        libzip-dev=1.5.0-1ubuntu1 \
        redis-server=6.2.3-1ubuntu1 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Clean up after installation
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
# Use COPY instead of ADD for clarity and to avoid side effects
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install Laravel Horizon globally
RUN composer global require laravel/horizon

# Copy existing application directory contents
COPY ./ /var/www/html

# Use a non-root user for running the application
# Avoid switching USER frequently to reduce complexity
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
