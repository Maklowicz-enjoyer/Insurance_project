FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy custom PHP configuration
COPY php.ini /usr/local/etc/php/php.ini
COPY session_config.php /usr/local/etc/php/session_config.php

# Create user for PHP-FPM
RUN groupadd -g 1000 www && \
    useradd -u 1000 -ms /bin/bash -g www www

# Change ownership
RUN chown -R www:www /var/www/html

# Switch to non-root user
USER www

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]
