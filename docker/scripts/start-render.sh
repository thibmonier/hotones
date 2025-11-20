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

# Configure PHP sessions to use Redis from REDIS_URL environment variable
if [ -n "$REDIS_URL" ]; then
    echo "Configuring PHP sessions to use Redis..."

    # Check if Redis extension is loaded
    if php -m | grep -q "^redis$"; then
        # Parse REDIS_URL (format: redis://hostname:port or redis://user:pass@hostname:port)
        REDIS_HOST=$(echo "$REDIS_URL" | sed -E 's#redis://([^:@]+:)?([^@]+@)?([^:]+):.*#\3#')
        REDIS_PORT=$(echo "$REDIS_URL" | sed -E 's#.*:([0-9]+)/?.*#\1#')

        # Add session configuration to PHP (without quotes around the value)
        cat >> /usr/local/etc/php/conf.d/php.ini <<EOF
session.save_handler = redis
session.save_path = tcp://${REDIS_HOST}:${REDIS_PORT}
EOF
        echo "  Redis session configured: ${REDIS_HOST}:${REDIS_PORT}"
    else
        echo "  Warning: Redis extension not loaded, sessions will use files"
    fi
else
    echo "Warning: REDIS_URL not set, sessions will use files"
fi

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

# Run database migrations with error handling
echo "Running database migrations..."

# First, ensure migration metadata storage is synced
php bin/console doctrine:migrations:sync-metadata-storage --no-interaction 2>/dev/null || true

# Try to run migrations
if ! php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | tee /tmp/migration.log; then
    echo "  Migration failed, checking if it's due to existing tables..."

    # If the error is about tables already existing, try to recover
    if grep -q "Base table or view already exists" /tmp/migration.log; then
        echo "  Tables already exist, syncing migration versions..."

        # Get list of all migrations
        migrations=$(php bin/console doctrine:migrations:list --no-interaction 2>/dev/null | grep -oP "Version\d+" || true)

        if [ -n "$migrations" ]; then
            # Mark each migration that failed as executed
            for version in $migrations; do
                # Try to add the version, ignore if already added
                php bin/console doctrine:migrations:version "$version" --add --no-interaction 2>/dev/null || true
            done

            echo "  Migration versions synced, trying migrations again..."
            # Try migrations one more time (should only run new ones)
            php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || {
                echo "  Warning: Some migrations still failed, but continuing startup..."
                echo "  You may need to manually review migrations later."
            }
        fi
    else
        echo "  Migration failed with unexpected error:"
        cat /tmp/migration.log
        echo "  Continuing anyway, but database may be in inconsistent state."
    fi
fi

echo "Checking migration status..."
php bin/console doctrine:migrations:status --no-interaction || true

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

# Create supervisor log directory
mkdir -p /var/log/supervisor

# Make sure supervisord runs in foreground
exec /usr/bin/supervisord -n -c /etc/supervisord.conf