# syntax=docker/dockerfile:1
# Dockerfile optimisé pour déploiement production sur Render
# Combine Nginx + PHP-FPM dans un seul conteneur

# ============================================
# Stage 1: Build JavaScript/CSS assets
# ============================================
FROM node:18-alpine AS assets
WORKDIR /app

# Install dependencies
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile --production=false

# Build assets
COPY assets/ assets/
COPY webpack.config.js ./
RUN yarn build

# ============================================
# Stage 2: Production PHP + Nginx image
# ============================================
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    nginx \
    supervisor \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    tzdata \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && pecl install redis \
  && docker-php-ext-enable redis \
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

# Configure PHP for production
COPY ./docker/php/php-prod.ini /usr/local/etc/php/conf.d/php.ini

# Configure PHP-FPM
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

# Configure Nginx
COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/nginx/conf.d/render.conf /etc/nginx/conf.d/default.conf

# Configure Supervisor to run both Nginx and PHP-FPM
COPY ./docker/supervisor/supervisord.conf /etc/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Copy built assets from assets stage
COPY --from=assets --chown=www-data:www-data /app/public/assets/ public/assets/

# Install PHP dependencies (production only, optimized)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --classmap-authoritative \
    --apcu-autoloader \
    && composer clear-cache

# Generate JWT keys if they don't exist
RUN mkdir -p config/jwt \
    && if [ ! -f config/jwt/private.pem ]; then \
        php bin/console lexik:jwt:generate-keypair --skip-if-exists; \
    fi

# Create necessary directories and set permissions
RUN mkdir -p var/cache var/log var/sessions \
    && chown -R www-data:www-data var/ public/ \
    && chmod -R 775 var/

# Warm up cache (will be re-warmed on startup with proper env vars)
RUN APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --no-warmup || true \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup || true

# Copy startup script
COPY ./docker/scripts/start-render.sh /usr/local/bin/start-render.sh
RUN chmod +x /usr/local/bin/start-render.sh

# Health check
COPY ./docker/scripts/healthcheck.php /var/www/html/public/health.php
RUN echo '<?php http_response_code(200); echo "OK";' > /var/www/html/public/health

# Expose port (Render uses PORT environment variable)
EXPOSE 8080

# Reset base image entrypoint and use our startup script
ENTRYPOINT []
CMD ["/usr/local/bin/start-render.sh"]