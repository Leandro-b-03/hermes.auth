# -----------------------------------------------------------------------------
# Project: Hermes
# Description: Dockerfile for setting up a PHP 8.3 environment with necessary
#              dependencies for a Laravel application.
# Maintainer: TWMS Team
# -----------------------------------------------------------------------------
FROM php:8.3-fpm

# Add metadata labels (following best practices)
LABEL org.opencontainers.image.title="Hermes Auth" \
      org.opencontainers.image.description="Laravel for Hermes Auth micro service" \
      org.opencontainers.image.version="0.0.1" \
      org.opencontainers.image.authors="Leandro Bezerra <leandro.b.03@gmail.com>" \
      org.opencontainers.image.source="https://github.com/Leandro-b-03/hermes/auth"

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    nano \
    && docker-php-ext-install pdo_mysql zip

# Set working directory
WORKDIR /var/www/html

# Copy composer files
# COPY composer.lock composer.json ./

# Install Composer dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Generate application key
# RUN php artisan key:generate

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Start worker
# RUN apt install -y supervisor
# COPY ./miscs/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port
EXPOSE 7002

# Start the server
CMD ["./entrypoint.sh"]