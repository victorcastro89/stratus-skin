# syntax=docker/dockerfile:1.7

FROM php:8.2-apache

ARG DEBIAN_FRONTEND=noninteractive

# Install required PHP extensions, build dependencies, and Node.js (for lessc in entrypoint)
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt/lists,sharing=locked \
    apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libldap2-dev \
        libpq-dev \
        libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        intl \
        zip \
        gd \
        exif \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        sockets \
    && docker-php-ext-configure ldap --with-libdir="lib/$(dpkg-architecture --query DEB_BUILD_MULTIARCH)" \
    && docker-php-ext-install -j"$(nproc)" ldap \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g --no-audit --no-fund less less-plugin-clean-css \
    && npm cache clean --force \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP settings for Roundcube
COPY roundcube.ini /usr/local/etc/php/conf.d/roundcube.ini

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Configure Apache to serve from public_html
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public_html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/temp /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html

# Copy and register the dev entrypoint
COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Expose port 80
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
