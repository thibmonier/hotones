#!/bin/bash
set -e

# Force production environment for all Symfony console commands
export APP_ENV=prod
export APP_DEBUG=0

echo "========================================="
echo "Starting HotOnes on Render (OPTIMIZED)"
echo "========================================="

# Environment check
echo "Environment: ${APP_ENV}"
echo "PHP Version: $(php -v | head -n 1)"

# Configure Nginx port from Render's PORT environment variable
PORT=${PORT:-8080}
echo "Configuring Nginx to listen on port $PORT..."
sed -i "s/listen 8080/listen $PORT/g" /etc/nginx/conf.d/default.conf

# Configure PHP sessions - Use files instead of Redis for now
echo "Configuring PHP sessions to use files..."
cat > /usr/local/etc/php/conf.d/99-session.ini <<EOF
session.save_handler = files
session.save_path = /var/www/html/var/sessions
EOF

# Ensure session directory exists with proper permissions
mkdir -p /var/www/html/var/sessions
chown -R www-data:www-data /var/www/html/var/sessions
chmod -R 770 /var/www/html/var/sessions

# ============================================
# OPTIMISATION 1: Parallel DB + Redis checks
# ============================================
echo "Checking service connectivity (parallel)..."

# Function to wait for database with exponential backoff
wait_for_database() {
    local max_attempts=15
    local attempt=0
    local wait_time=1

    while [ $attempt -lt $max_attempts ]; do
        if php bin/console dbal:run-sql "SELECT 1" > /dev/null 2>&1; then
            echo "  ✓ Database ready (attempt $((attempt + 1)))"
            return 0
        fi

        attempt=$((attempt + 1))
        if [ $attempt -lt $max_attempts ]; then
            echo "  ⏳ Database not ready, retrying in ${wait_time}s... ($attempt/$max_attempts)"
            sleep $wait_time
            # Exponential backoff: 1s, 2s, 4s, 8s, max 8s
            wait_time=$((wait_time < 8 ? wait_time * 2 : 8))
        fi
    done

    echo "  ⚠️  Database connection timeout after $max_attempts attempts"
    php bin/console dbal:run-sql "SELECT 1" 2>&1 || true
    return 1
}

# Function to wait for Redis with exponential backoff
wait_for_redis() {
    if [ -z "$REDIS_URL" ]; then
        echo "  ⊘ No REDIS_URL configured, skipping"
        return 0
    fi

    local max_attempts=10
    local attempt=0
    local wait_time=1

    while [ $attempt -lt $max_attempts ]; do
        if php -r "
            \$redis = new Redis();
            \$url = parse_url(getenv('REDIS_URL'));
            \$host = \$url['host'] ?? 'localhost';
            \$port = \$url['port'] ?? 6379;
            \$pass = \$url['pass'] ?? null;
            try {
                \$redis->connect(\$host, \$port, 1);
                if (\$pass) \$redis->auth(\$pass);
                \$redis->ping();
                exit(0);
            } catch (Exception \$e) {
                exit(1);
            }
        " > /dev/null 2>&1; then
            echo "  ✓ Redis ready (attempt $((attempt + 1)))"
            return 0
        fi

        attempt=$((attempt + 1))
        if [ $attempt -lt $max_attempts ]; then
            echo "  ⏳ Redis not ready, retrying in ${wait_time}s... ($attempt/$max_attempts)"
            sleep $wait_time
            wait_time=$((wait_time < 4 ? wait_time * 2 : 4))
        fi
    done

    echo "  ⚠️  Redis connection timeout, cache warmup may be slower"
    return 1
}

# Run checks in parallel (économise ~15-30 secondes)
wait_for_database &
db_pid=$!
wait_for_redis &
redis_pid=$!

# Wait for both to complete
wait $db_pid
db_status=$?
wait $redis_pid
redis_status=$?

if [ $db_status -ne 0 ]; then
    echo "⚠️  WARNING: Database not available, some operations may fail"
fi

# ============================================
# OPTIMISATION 2: Quick JWT key generation
# ============================================
echo "Checking JWT keys..."
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "  🔑 Generating JWT keypair..."
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
    echo "  ✓ JWT keys generated"
else
    echo "  ✓ JWT keys found"
fi

# ============================================
# OPTIMISATION 3: Smart migration handling
# ============================================
echo "Checking database migrations..."

# Sync metadata storage (fast operation)
php bin/console doctrine:migrations:sync-metadata-storage --no-interaction 2>/dev/null || true

# Check if migrations are needed (faster than running all migrations)
migration_status=$(php bin/console doctrine:migrations:up-to-date --no-interaction 2>&1 || echo "needed")

if echo "$migration_status" | grep -q "up-to-date"; then
    echo "  ✓ Database schema up-to-date, skipping migrations"
else
    echo "  🔄 Running database migrations..."

    if ! php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 | tee /tmp/migration.log; then
        echo "  ⚠️  Migration failed, attempting recovery..."

        # If the error is about tables already existing, try to recover
        if grep -q "Base table or view already exists" /tmp/migration.log; then
            echo "  🔧 Syncing migration versions..."

            # Get list of all migrations and mark as executed
            migrations=$(php bin/console doctrine:migrations:list --no-interaction 2>/dev/null | grep -oP "Version\d+" || true)

            if [ -n "$migrations" ]; then
                for version in $migrations; do
                    php bin/console doctrine:migrations:version "$version" --add --no-interaction 2>/dev/null || true
                done

                # Retry migrations
                php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || {
                    echo "  ⚠️  Some migrations still failed, continuing..."
                }
            fi
        else
            echo "  ❌ Migration failed:"
            cat /tmp/migration.log
            echo "  Continuing anyway..."
        fi
    else
        echo "  ✓ Migrations completed successfully"
    fi
fi

# ============================================
# OPTIMISATION 4: Cache warmup (single pass, startup only)
# ============================================
echo "Building application cache..."

# Clear existing cache silently
php bin/console cache:clear --no-warmup 2>&1 | grep -v "Clearing cache" || true

# Warm up cache with timing
start_time=$(date +%s)
if php bin/console cache:warmup 2>&1 | tail -5; then
    end_time=$(date +%s)
    duration=$((end_time - start_time))
    echo "  ✓ Cache warmed up in ${duration}s"
else
    echo "  ⚠️  Cache warmup had warnings, but continuing..."
fi

# ============================================
# OPTIMISATION 5: Parallel permission setting
# ============================================
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/var &
chmod -R 775 /var/www/html/var &
wait
echo "  ✓ Permissions set"

# Display application info (condensed)
echo "========================================="
echo "Application ready!"
echo "========================================="
php bin/console about | head -20

# ============================================
# Start services
# ============================================
echo "Starting services with supervisord..."
echo "  Port: $PORT"
echo "  Environment: $APP_ENV"
echo "  Working directory: $(pwd)"

# Create supervisor log directory
mkdir -p /var/log/supervisor

# Start supervisord in foreground
exec /usr/bin/supervisord -n -c /etc/supervisord.conf
