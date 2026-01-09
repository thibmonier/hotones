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

# Configure PHP sessions - Use files instead of Redis for now
# TODO: Debug Redis connectivity issues (messenger workers timing out)
echo "Configuring PHP sessions to use files (Redis connectivity issues)..."
cat > /usr/local/etc/php/conf.d/99-session.ini <<EOF
session.save_handler = files
session.save_path = /var/www/html/var/sessions
EOF
echo "  ✓ File-based sessions configured"

# Ensure session directory exists with proper permissions
mkdir -p /var/www/html/var/sessions
chown -R www-data:www-data /var/www/html/var/sessions
chmod -R 770 /var/www/html/var/sessions

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

# Wait for Redis to be ready (for cache)
if [ -n "$REDIS_URL" ]; then
    echo "Waiting for Redis connection..."
    echo "REDIS_URL: ${REDIS_URL:0:30}..." # Show first 30 chars only for security

    max_redis_attempts=15
    redis_attempt=0

    # Extract Redis connection info from URL for testing
    until php -r "
        \$redis = new Redis();
        \$url = parse_url(getenv('REDIS_URL'));
        \$host = \$url['host'] ?? 'localhost';
        \$port = \$url['port'] ?? 6379;
        \$pass = \$url['pass'] ?? null;
        try {
            \$redis->connect(\$host, \$port, 2);
            if (\$pass) \$redis->auth(\$pass);
            \$redis->ping();
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " > /dev/null 2>&1 || [ $redis_attempt -eq $max_redis_attempts ]; do
        redis_attempt=$((redis_attempt + 1))
        echo "  Attempt $redis_attempt/$max_redis_attempts - Redis not ready, waiting..."
        sleep 1
    done

    if [ $redis_attempt -eq $max_redis_attempts ]; then
        echo "WARNING: Redis connection timeout after $max_redis_attempts attempts"
        echo "Cache warmup may fail or be slower without Redis"
    else
        echo "Redis connection established!"
    fi
else
    echo "No REDIS_URL configured, skipping Redis check"
fi

# Generate JWT keys if not present
echo "Checking JWT keys..."
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "  Generating JWT keypair..."
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
fi

# Install bundle assets (EasyAdmin, API Platform, etc.)
echo "Installing bundle assets..."
php bin/console assets:install public --no-interaction

# Run database migrations with error handling
echo "Running database migrations..."

# First, ensure migration metadata storage is synced
php bin/console doctrine:migrations:sync-metadata-storage --no-interaction 2>/dev/null || true

# Check if there are migrations to run
echo "Checking for pending migrations..."
php bin/console doctrine:migrations:status --no-interaction | tee /tmp/migration_status.log || true

# Count available migrations
available_migrations=$(grep -c "not migrated" /tmp/migration_status.log || echo "0")
echo "Found $available_migrations pending migration(s)"

if [ "$available_migrations" -gt 0 ]; then
    echo "Executing pending migrations..."
    # Force execution by running each migration individually
    pending_versions=$(grep "not migrated" /tmp/migration_status.log | grep -oP "Version\d+" || true)

    for version in $pending_versions; do
        echo "  Migrating $version..."
        php bin/console doctrine:migrations:execute --up "$version" --no-interaction || {
            echo "  ERROR: Migration $version failed!"
            # Check if it's because table already exists
            if php bin/console doctrine:migrations:execute --up "$version" --no-interaction 2>&1 | grep -q "already exists"; then
                echo "  Tables already exist, marking as migrated..."
                php bin/console doctrine:migrations:version "$version" --add --no-interaction || true
            else
                echo "  Continuing with remaining migrations..."
            fi
        }
    done

    echo "All migrations processed."
else
    echo "No pending migrations to execute."
fi

echo "Final migration status:"
php bin/console doctrine:migrations:status --no-interaction || true

# Clear and warm up cache
echo "Clearing cache..."
php bin/console cache:clear --no-warmup

echo "Warming up cache..."
# Skip optional warmers to avoid Doctrine metadata issues if schema is not yet migrated
php bin/console cache:warmup --no-optional-warmers || {
    echo "  Cache warmup with --no-optional-warmers failed, trying without flag..."
    php bin/console cache:warmup || {
        echo "  WARNING: Cache warmup failed, but continuing..."
        echo "  Cache will be built on first request."
    }
}

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