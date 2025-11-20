#!/bin/bash
set -e

# Force production environment for all Symfony console commands
export APP_ENV=prod
export APP_DEBUG=0

echo "========================================="
echo "Starting HotOnes on Render"
echo "========================================="

# Environment check
echo "Environment: ${APP_ENV}"
echo "PHP Version: $(php -v | head -n 1)"

# Configure Nginx port from Render's PORT environment variable
PORT=${PORT:-8080}
echo "Configuring Nginx to listen on port $PORT..."
sed -i "s/listen 8080/listen $PORT/g" /etc/nginx/conf.d/default.conf

# Wait for database to be ready
echo "Waiting for database connection..."
echo "DATABASE_URL: ${DATABASE_URL:0:30}..." # Show first 30 chars only for security

max_attempts=30
attempt=0

until php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1 || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "  Attempt $attempt/$max_attempts - Database not ready, waiting..."
    if [ $attempt -eq 5 ]; then
        echo "  Debug: Testing database connection..."
        php bin/console dbal:run-sql "SELECT 1" 2>&1 || true
    fi
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "ERROR: Database connection timeout after $max_attempts attempts"
    echo "Last error output:"
    php bin/console dbal:run-sql "SELECT 1" 2>&1 || true
    echo "Continuing anyway to start services (for debugging)..."
    # Don't exit, let's see what happens
fi

echo "Database connection established!"

# Generate JWT keys if not present
echo "Checking JWT keys..."
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "  Generating JWT keypair..."
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
fi

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Clear and warm up cache
echo "Clearing cache..."
php bin/console cache:clear --no-warmup

echo "Warming up cache..."
php bin/console cache:warmup

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

# Display application info
echo "========================================="
echo "Application ready!"
echo "========================================="
php bin/console about

# Start supervisord (Nginx + PHP-FPM + Messenger workers)
echo "Starting services with supervisord..."
echo "Port: $PORT"
echo "Working directory: $(pwd)"

# Make sure supervisord runs in foreground
exec /usr/bin/supervisord -n -c /etc/supervisord.conf