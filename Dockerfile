FROM composer:2.6 AS composer

# Use the official PHP image as a base image to construct our own image from
FROM php:8.2.12-apache

# Install system dependencies (git, unzip, and libzip-dev for the zip extension)
# Install and enable mysql modules for PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
&& rm -rf /var/lib/apt/lists/* \
&& docker-php-ext-install mysqli pdo pdo_mysql && \
    docker-php-ext-enable mysqli pdo pdo_mysql

# Copy Composer from the composer stage
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Set an environment variable which contains the apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Update the apache configuration using the `APACHE_DOCUMENT_ROOT` environment variable
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
&& sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
