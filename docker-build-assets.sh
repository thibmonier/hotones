#!/bin/bash

# Script pour builder les assets dans le container Docker
# Usage: ./docker-build-assets.sh [dev|prod|watch]

set -e

MODE=${1:-dev}
CONTAINER_NAME="hotones_app"

echo "🐳 Building HotOnes assets in Docker container ($MODE mode)..."

# Vérifier que le container existe et fonctionne
if ! docker ps --format "table {{.Names}}" | grep -q "$CONTAINER_NAME"; then
    echo "❌ Container $CONTAINER_NAME n'est pas en cours d'exécution"
    echo "🚀 Démarrage avec: docker compose up -d"
    docker compose up -d
    sleep 3
fi

echo "📦 Installing Node.js and dependencies in container..."
docker compose exec app sh -c "
    apk add --no-cache nodejs npm yarn && \
    yarn install
"

case $MODE in
    "dev")
        echo "🔨 Building development assets in container..."
        docker compose exec app yarn dev
        echo "📋 Copying additional theme files..."
        docker compose exec app sh -c "cp -f node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css public/assets/libs/select2/css/ 2>/dev/null || echo '⚠️  Warning: Select2 Bootstrap 5 theme not found'"
        echo "✅ Development assets built successfully!"
        ;;
    "prod"|"production")
        echo "🔨 Building production assets in container..."
        docker compose exec app yarn build
        echo "📋 Copying additional theme files..."
        docker compose exec app sh -c "cp -f node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css public/assets/libs/select2/css/ 2>/dev/null || echo '⚠️  Warning: Select2 Bootstrap 5 theme not found'"
        echo "✅ Production assets built successfully!"
        ;;
    "watch")
        echo "📋 Copying additional theme files..."
        docker compose exec app sh -c "cp -f node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css public/assets/libs/select2/css/ 2>/dev/null || echo '⚠️  Warning: Select2 Bootstrap 5 theme not found'"
        echo "👀 Starting watch mode in container..."
        echo "⚠️  Press Ctrl+C to stop watching"
        docker compose exec app yarn watch
        ;;
    *)
        echo "❌ Invalid mode: $MODE"
        echo "Usage: $0 [dev|prod|watch]"
        exit 1
        ;;
esac

echo ""
echo "📁 Assets generated in: public/assets/"
echo "📊 Checking assets in container..."
docker compose exec app sh -c "
    ls -la public/assets/ 2>/dev/null || echo 'No assets directory found'
    if [ -d public/assets/ ]; then
        echo 'Asset files:'
        find public/assets/ -name '*.js' -o -name '*.css' 2>/dev/null | wc -l | sed 's/^/   /'
        echo 'Total size:'
        du -sh public/assets/ 2>/dev/null | cut -f1 | sed 's/^/   /'
    fi
"