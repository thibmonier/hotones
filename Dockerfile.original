# syntax=docker/dockerfile:1
# Dockerfile optimisé pour déploiement production sur Render
# Combine Nginx + PHP-FPM dans un seul conteneur

# ============================================
# Stage 1: Build JavaScript/CSS assets
# ============================================
FROM node:22-alpine AS assets
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
FROM php:8.5-fpm-alpine

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
    # Build dependencies (will be removed after)
    && apk add --no-cache --virtual .build-deps \
    autoconf \
    g++ \
    make \
  && docker-php-ext-configure intl \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && pecl install apcu \
  && docker-php-ext-enable apcu \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && docker-php-ext-install pdo \
  && docker-php-ext-install pdo_mysql \
  && docker-php-ext-install intl \
  && docker-php-ext-install gd \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install zip \
  # Remove build dependencies to keep image small
  && apk del .build-deps \
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
    --no-scripts \
    --classmap-authoritative \
    --apcu-autoloader \
    --ignore-platform-req=php \
    && composer clear-cache

# Install AssetMapper vendor files and compile assets
# Use SQLite for build-time database connection (DB container not available during build)
RUN APP_ENV=prod DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" php bin/console importmap:install \
    && APP_ENV=prod DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" php bin/console asset-map:compile

# Install bundle assets (EasyAdmin, API Platform, etc.) to public/bundles/
# Use hard copy (not symlink) for production compatibility
RUN APP_ENV=prod DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" php bin/console assets:install public

# Create JWT directory (keys will be generated on first startup)
RUN mkdir -p config/jwt

# Create necessary directories and set permissions
RUN mkdir -p var/cache var/log var/sessions \
    && chown -R www-data:www-data var/ public/ \
    && chmod -R 775 var/

# Warm up cache (will be re-warmed on startup with proper env vars)
# Use SQLite for build-time to avoid DB connection errors
RUN APP_ENV=prod APP_DEBUG=0 DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" php bin/console cache:clear --no-warmup || true \
    && APP_ENV=prod APP_DEBUG=0 DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" php bin/console cache:warmup || true

# Copy startup script
COPY ./docker/scripts/start-render.sh /usr/local/bin/start-render.sh
RUN chmod +x /usr/local/bin/start-render.sh

# Create simple health check endpoint
RUN echo '<?php http_response_code(200); echo "OK";' > /var/www/html/public/health

# Expose port (Render uses PORT environment variable)
EXPOSE 8080

# Reset base image entrypoint and use our startup script
ENTRYPOINT []
CMD ["/usr/local/bin/start-render.sh"]