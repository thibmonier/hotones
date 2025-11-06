# syntax=docker/dockerfile:1

# Stage 1: Build assets
FROM node:18-alpine AS assets
WORKDIR /app
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile
COPY assets/ assets/
COPY webpack.config.js ./
RUN yarn build

# Stage 2: PHP application
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    tzdata \
    shadow \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && docker-php-ext-install \
    pdo \
    pdo_mysql \
    intl \
    opcache \
    gd \
    bcmath \
    zip \
  && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www/html

# Copy built assets from assets stage
COPY --from=assets /app/public/assets/ public/assets/

# Optimize permissions for local dev
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data || true
RUN chown -R www-data:www-data /var/www/html

USER www-data

# Warm up Composer cache (no-op if vendor already present)
RUN [ -f composer.json ] || exit 0
