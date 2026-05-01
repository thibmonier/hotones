#!/bin/bash
# US-070 — Boot script pour Render staging (free tier).
#
# Difference vs start-render.sh :
#   - SQLite ephemere : DROP la base au boot puis recharge fixtures demo
#     (free tier sans persistent disk = data perdue de toute facon a
#     chaque cold start, on en profite pour partir d'un etat connu)
#   - Pas d'attente Redis (transport in-memory)
#   - Pas de supervisord prod : on lance Nginx + PHP-FPM en foreground
set -e

export APP_ENV=prod
export APP_DEBUG=0

echo "========================================="
echo "Starting HotOnes STAGING (Render free tier)"
echo "========================================="

PORT=${PORT:-8080}
echo "Configuring Nginx to listen on port $PORT..."
sed -i "s/listen 8080/listen $PORT/g" /etc/nginx/conf.d/default.conf

# Sessions: filesystem (Redis indisponible)
cat > /usr/local/etc/php/conf.d/99-session.ini <<'EOF'
session.save_handler = files
session.save_path = /var/www/html/var/sessions
EOF
mkdir -p /var/www/html/var/sessions
chown -R www-data:www-data /var/www/html/var/sessions
chmod -R 770 /var/www/html/var/sessions

# JWT: regenere a chaque boot (free tier sans disque persistent => clés
# perdues aux cold starts ; mieux vaut repartir clean)
mkdir -p config/jwt
echo "Regenerating JWT keypair..."
php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction

# Reset SQLite + schema + fixtures demo
echo "Wiping ephemeral SQLite database..."
rm -f var/staging.db var/staging.db-journal

echo "Creating schema from Doctrine migrations..."
php bin/console doctrine:database:create --if-not-exists --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Loading reference data + fixtures + demo users..."
php bin/console app:load-reference-data --no-interaction || true
php bin/console doctrine:fixtures:load --no-interaction --append || true
php bin/console app:create-test-users --no-interaction || true

echo "Seeding demo projects (small dataset for free tier)..."
php bin/console app:seed-projects-2025 --count=5 --no-interaction || true

echo "Installing bundle assets..."
php bin/console assets:install public --no-interaction

echo "Clearing + warming cache..."
php bin/console cache:clear --no-warmup
php bin/console cache:warmup --no-optional-warmers || true

chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

echo "========================================="
echo "Staging boot complete. Demo accounts :"
echo "  intervenant@test.com / password"
echo "  manager@test.com     / password"
echo "  admin@test.com       / password"
echo "========================================="
php bin/console about | head -20

# Direct foreground (no supervisord on free tier)
mkdir -p /var/log
php-fpm -D
exec nginx -g 'daemon off;'
