# ============================================================
# URL Shortener Service - PHP-FPM Backend Dockerfile
# ============================================================
# This Dockerfile builds the PHP-FPM container for the Yii2
# backend application with all required dependencies.
# ============================================================

# Use PHP 8.5 FPM Alpine Linux as base image
FROM php:8.5-fpm-alpine

# ----------------------------------------------------------
# Environment Configuration
# ----------------------------------------------------------
ARG PHP_FPM_USER=www-data
ARG PHP_FPM_GROUP=www-data
ARG APP_ENV=production

ENV PHP_FPM_USER=${PHP_FPM_USER} \
    PHP_FPM_GROUP=${PHP_FPM_GROUP} \
    APP_ENV=${APP_ENV} \
    COMPOSER_ALLOW_SUPERUSER=1

# ----------------------------------------------------------
# Install System Dependencies
# ----------------------------------------------------------
RUN apk add --no-cache \
    # Core utilities
    bash \
    curl \
    wget \
    git \
    unzip \
    # Database client
    mariadb-client \
    # Image processing for QR codes
    # gd extension dependencies
    libjpeg-turbo-dev \
    libpng-dev \
    freetype-dev \
    # intl extension
    icu-dev \
    # zip extension
    libzip-dev \
    # BCMath for QR generation
    bcmath \
    # XML for Yii2
    libxml2-dev \
    # Supervisor for process management
    supervisor

# ----------------------------------------------------------
# Install PHP Extensions
# ----------------------------------------------------------
RUN docker-php-ext-configure gd --withjpeg --withfreetype \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        intl \
        zip \
        bcmath \
        gd \
        xml \
        mbstring \
        json \
        curl

# ----------------------------------------------------------
# Install Composer
# ----------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ----------------------------------------------------------
# Create Application Directory
# ----------------------------------------------------------
RUN mkdir -p /var/www/html && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

WORKDIR /var/www/html

# ----------------------------------------------------------
# Copy Application Files
# ----------------------------------------------------------
# Copy composer files first for dependency installation
COPY backend/composer.json /var/www/html/
COPY backend/composer.lock /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ----------------------------------------------------------
# Copy Backend Application
# ----------------------------------------------------------
COPY backend/ /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    mkdir -p /var/www/html/runtime && \
    chown -R www-data:www-data /var/www/html/runtime

# ----------------------------------------------------------
# Configure Supervisor
# ----------------------------------------------------------
RUN mkdir -p /etc/supervisor/conf.d

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ----------------------------------------------------------
# Expose Port
# ----------------------------------------------------------
EXPOSE 9000

# ----------------------------------------------------------
# Start Supervisor (manages PHP-FPM)
# ----------------------------------------------------------
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
