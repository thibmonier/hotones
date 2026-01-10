#!/bin/bash
#
# Script de benchmark pour comparer les performances des Dockerfiles
# Usage: ./docker-benchmark.sh
#

set -e

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "=========================================="
echo "  Docker Build Performance Benchmark"
echo "=========================================="
echo ""

# Vérifier que BuildKit est activé
if [ -z "$DOCKER_BUILDKIT" ] || [ "$DOCKER_BUILDKIT" != "1" ]; then
    echo -e "${YELLOW}⚠️  BuildKit n'est pas activé. Activation...${NC}"
    export DOCKER_BUILDKIT=1
fi

echo -e "${GREEN}✓ BuildKit activé${NC}"
echo ""

# Fonction pour mesurer le temps de build
benchmark_build() {
    local dockerfile=$1
    local tag=$2
    local label=$3
    
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $label${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    # Nettoyage avant build
    echo "🧹 Nettoyage des images existantes..."
    docker rmi -f $tag 2>/dev/null || true
    echo ""
    
    # Build avec mesure du temps
    echo "🔨 Build en cours..."
    local start_time=$(date +%s)
    
    docker build \
        -f "$dockerfile" \
        -t "$tag" \
        --progress=plain \
        . 2>&1 | tee "build-log-$tag.txt"
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    echo ""
    echo -e "${GREEN}✓ Build terminé en ${duration}s${NC}"
    echo "$duration" > "build-time-$tag.txt"
    echo ""
    
    # Taille de l'image
    local size=$(docker images $tag --format "{{.Size}}")
    echo -e "${GREEN}📦 Taille de l'image: $size${NC}"
    echo "$size" > "build-size-$tag.txt"
    echo ""
}

# 1. Clean build du Dockerfile original
echo ""
echo "🧪 TEST 1: Build complet (cold build) - Dockerfile ORIGINAL"
echo ""
docker builder prune -af --filter "until=1h" 2>/dev/null || true
benchmark_build "Dockerfile" "hotones:original" "Dockerfile Original (Cold Build)"

# Petit délai pour séparer
sleep 2

# 2. Clean build du Dockerfile optimisé
echo ""
echo "🧪 TEST 2: Build complet (cold build) - Dockerfile OPTIMISÉ"
echo ""
docker builder prune -af --filter "until=1h" 2>/dev/null || true
benchmark_build "Dockerfile.optimized" "hotones:optimized" "Dockerfile Optimisé (Cold Build)"

# Petit délai pour séparer
sleep 2

# 3. Rebuild du Dockerfile original (warm)
echo ""
echo "🧪 TEST 3: Rebuild (warm build) - Dockerfile ORIGINAL"
echo ""
benchmark_build "Dockerfile" "hotones:original-warm" "Dockerfile Original (Warm Build)"

# Petit délai pour séparer
sleep 2

# 4. Rebuild du Dockerfile optimisé (warm)
echo ""
echo "🧪 TEST 4: Rebuild (warm build) - Dockerfile OPTIMISÉ"
echo ""
benchmark_build "Dockerfile.optimized" "hotones:optimized-warm" "Dockerfile Optimisé (Warm Build)"

# Résumé des résultats
echo ""
echo "=========================================="
echo "  📊 RÉSULTATS DU BENCHMARK"
echo "=========================================="
echo ""

original_cold=$(cat build-time-hotones:original.txt)
optimized_cold=$(cat build-time-hotones:optimized.txt)
original_warm=$(cat build-time-hotones:original-warm.txt)
optimized_warm=$(cat build-time-hotones:optimized-warm.txt)

original_size=$(cat build-size-hotones:original.txt)
optimized_size=$(cat build-size-hotones:optimized.txt)

echo "┌─────────────────────────┬─────────────┬──────────────┬─────────┐"
echo "│ Test                    │ Original    │ Optimisé     │ Gain    │"
echo "├─────────────────────────┼─────────────┼──────────────┼─────────┤"
printf "│ Cold Build              │ %9ss │ %10ss │ " "$original_cold" "$optimized_cold"
gain_cold=$(echo "scale=1; (($original_cold - $optimized_cold) / $original_cold) * 100" | bc)
printf "%6.1f%% │\n" "$gain_cold"

printf "│ Warm Build              │ %9ss │ %10ss │ " "$original_warm" "$optimized_warm"
gain_warm=$(echo "scale=1; (($original_warm - $optimized_warm) / $original_warm) * 100" | bc)
printf "%6.1f%% │\n" "$gain_warm"

echo "├─────────────────────────┼─────────────┼──────────────┼─────────┤"
printf "│ Taille image            │ %11s │ %12s │         │\n" "$original_size" "$optimized_size"
echo "└─────────────────────────┴─────────────┴──────────────┴─────────┘"
echo ""

# Recommandation
if (( $(echo "$gain_warm > 30" | bc -l) )); then
    echo -e "${GREEN}✅ RECOMMANDATION: Le Dockerfile optimisé est significativement plus rapide !${NC}"
    echo -e "${GREEN}   Migration recommandée vers Dockerfile.optimized${NC}"
else
    echo -e "${YELLOW}⚠️  Les gains sont modérés. Vérifiez si les cache mounts fonctionnent.${NC}"
fi

echo ""
echo "📝 Les logs détaillés sont dans:"
echo "   - build-log-hotones:original.txt"
echo "   - build-log-hotones:optimized.txt"
echo "   - build-log-hotones:original-warm.txt"
echo "   - build-log-hotones:optimized-warm.txt"
echo ""

# Nettoyage des fichiers temporaires
echo "🧹 Nettoyage des fichiers temporaires..."
rm -f build-time-*.txt build-size-*.txt
echo ""

echo -e "${GREEN}✓ Benchmark terminé !${NC}"
