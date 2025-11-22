#!/bin/bash

# Script pour builder les assets HotOnes
# Usage: ./build-assets.sh [dev|prod|watch]

set -e

MODE=${1:-dev}

echo "🎨 Building HotOnes assets in $MODE mode..."

case $MODE in
    "dev")
        echo "📦 Installing dependencies..."
        yarn install
        echo "🔨 Building development assets..."
        yarn dev
        echo "📋 Copying additional theme files..."
        cp -f node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css public/assets/libs/select2/css/ 2>/dev/null || echo "⚠️  Warning: Select2 Bootstrap 5 theme not found"
        echo "✅ Development assets built successfully!"
        ;;
    "prod"|"production")
        echo "📦 Installing dependencies..."
        yarn install
        echo "🔨 Building production assets..."
        yarn build
        echo "📋 Copying additional theme files..."
        cp -f node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css public/assets/libs/select2/css/ 2>/dev/null || echo "⚠️  Warning: Select2 Bootstrap 5 theme not found"
        echo "✅ Production assets built successfully!"
        ;;
    "watch")
        echo "📦 Installing dependencies..."
        yarn install
        echo "📋 Copying additional theme files..."
        cp -f node_modules/select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css public/assets/libs/select2/css/ 2>/dev/null || echo "⚠️  Warning: Select2 Bootstrap 5 theme not found"
        echo "👀 Starting watch mode..."
        yarn watch
        ;;
    *)
        echo "❌ Invalid mode: $MODE"
        echo "Usage: $0 [dev|prod|watch]"
        exit 1
        ;;
esac

echo ""
echo "📁 Assets generated in: public/assets/"
echo "📊 Asset summary:"
ls -la public/assets/ | grep -E '\.(js|css)$' | wc -l | xargs echo "   Files:"
du -sh public/assets/ | cut -f1 | xargs echo "   Size:"