#!/bin/bash
# Script d'analyse de compatibilité pour migration PHP 8.5 / Symfony 8
# Usage: ./scripts/check-migration-compatibility.sh

set -e

echo "=========================================="
echo "🔍 Analyse de compatibilité Migration"
echo "PHP 8.4 → 8.5 | Symfony 7.3 → 8.0"
echo "=========================================="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Détection du répertoire du projet
PROJECT_ROOT=$(cd "$(dirname "$0")/.." && pwd)

# 1. Vérifier les versions actuelles
echo -e "${BLUE}📦 Versions actuelles:${NC}"
echo "----------------------------"
php -v | head -n 1
composer show symfony/framework-bundle | grep versions
echo ""

# 2. Lister les dépendances Symfony
echo -e "${BLUE}📚 Packages Symfony installés:${NC}"
echo "----------------------------"
composer show | grep "^symfony/" | awk '{print $1, $2}'
echo ""

# 3. Vérifier les dépendances obsolètes
echo -e "${BLUE}⚠️  Packages obsolètes:${NC}"
echo "----------------------------"
composer outdated --direct 2>/dev/null || echo "Aucune information disponible"
echo ""

# 4. Rechercher les dépréciations dans le code
echo -e "${BLUE}🔎 Recherche de dépréciations dans le code:${NC}"
echo "----------------------------"
DEPRECATED_COUNT=$(grep -r "@deprecated" src/ 2>/dev/null | wc -l | tr -d ' ')
if [ "$DEPRECATED_COUNT" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  $DEPRECATED_COUNT usages de code déprécié trouvés${NC}"
    echo "Détails:"
    grep -r "@deprecated" src/ 2>/dev/null | head -n 10
    [ "$DEPRECATED_COUNT" -gt 10 ] && echo "... ($(($DEPRECATED_COUNT - 10)) autres)"
else
    echo -e "${GREEN}✅ Aucune dépréciation explicite trouvée${NC}"
fi
echo ""

# 5. Vérifier les bundles critiques
echo -e "${BLUE}🎯 Bundles critiques à vérifier pour Symfony 8:${NC}"
echo "----------------------------"

CRITICAL_BUNDLES=(
    "api-platform/core"
    "doctrine/doctrine-bundle"
    "doctrine/orm"
    "lexik/jwt-authentication-bundle"
    "scheb/2fa-bundle"
    "endroid/qr-code-bundle"
    "knplabs/knp-paginator-bundle"
    "sentry/sentry-symfony"
    "gedmo/doctrine-extensions"
)

for bundle in "${CRITICAL_BUNDLES[@]}"; do
    VERSION=$(composer show "$bundle" 2>/dev/null | grep "versions" | awk '{print $3}')
    if [ -n "$VERSION" ]; then
        echo "  • $bundle: $VERSION"
    fi
done
echo ""

# 6. Analyser les attributs PHP
echo -e "${BLUE}🏷️  Utilisation des attributs PHP:${NC}"
echo "----------------------------"
ATTRIBUTE_COUNT=$(grep -r "#\[" src/ 2>/dev/null | wc -l | tr -d ' ')
echo "  • Nombre d'attributs trouvés: $ATTRIBUTE_COUNT"
echo "  • Les attributs sont le standard PHP 8+"
echo ""

# 7. Vérifier les polyfills
echo -e "${BLUE}🔧 Polyfills dans composer.json:${NC}"
echo "----------------------------"
if grep -q "symfony/polyfill-php83" composer.json; then
    echo -e "${YELLOW}⚠️  Des polyfills PHP 8.3 sont présents${NC}"
    echo "  → À retirer lors de la migration vers PHP 8.5"
else
    echo -e "${GREEN}✅ Pas de polyfills PHP 8.3 détectés${NC}"
fi
echo ""

# 8. Tester si Symfony 8 est disponible
echo -e "${BLUE}🌐 Disponibilité de Symfony 8:${NC}"
echo "----------------------------"
SYMFONY8_CHECK=$(composer show symfony/framework-bundle --all 2>/dev/null | grep -o "8\.[0-9]" | head -n 1 || echo "")
if [ -n "$SYMFONY8_CHECK" ]; then
    echo -e "${GREEN}✅ Symfony 8 est disponible sur Packagist: $SYMFONY8_CHECK${NC}"
else
    echo -e "${YELLOW}⏳ Symfony 8 n'est pas encore disponible${NC}"
    echo "  → Prévu pour novembre 2025"
fi
echo ""

# 9. Analyser les extensions PHP requises
echo -e "${BLUE}🔌 Extensions PHP requises:${NC}"
echo "----------------------------"
REQUIRED_EXTS=$(grep "ext-" composer.json | grep -o "ext-[a-z0-9_]*" | sort -u)
echo "$REQUIRED_EXTS" | while read -r ext; do
    EXT_NAME=${ext#ext-}
    if php -m | grep -qi "^$EXT_NAME$"; then
        echo -e "  ${GREEN}✅${NC} $ext (installée)"
    else
        echo -e "  ${RED}❌${NC} $ext (non installée)"
    fi
done
echo ""

# 10. Vérification Dockerfile et images Docker
echo -e "${BLUE}🐳 Configuration Docker:${NC}"
echo "----------------------------"

if [ -f "$PROJECT_ROOT/Dockerfile" ]; then
    CURRENT_PHP_IMAGE=$(grep "^FROM php:" "$PROJECT_ROOT/Dockerfile" | head -n 1 | awk '{print $2}')
    echo "  • Image PHP actuelle: $CURRENT_PHP_IMAGE"

    if echo "$CURRENT_PHP_IMAGE" | grep -q "8.4"; then
        echo -e "  ${YELLOW}⚠️  Migration requise vers: php:8.5-fpm-alpine${NC}"
    elif echo "$CURRENT_PHP_IMAGE" | grep -q "8.5"; then
        echo -e "  ${GREEN}✅${NC} Déjà sur PHP 8.5"
    fi

    # Vérifier Node.js pour assets
    NODE_IMAGE=$(grep "^FROM node:" "$PROJECT_ROOT/Dockerfile" | head -n 1 | awk '{print $2}')
    if [ -n "$NODE_IMAGE" ]; then
        echo "  • Image Node.js: $NODE_IMAGE"
        if echo "$NODE_IMAGE" | grep -q "18"; then
            echo -e "  ${YELLOW}⚠️  Node 18 (LTS se termine en avril 2025, migrer vers Node 20 ou 22)${NC}"
        fi
    fi

    # Vérifier les services dans docker-compose.yml
    if [ -f "$PROJECT_ROOT/docker-compose.yml" ]; then
        echo ""
        echo "  Services Docker Compose:"

        DB_IMAGE=$(grep "image: mariadb" "$PROJECT_ROOT/docker-compose.yml" | awk '{print $2}')
        [ -n "$DB_IMAGE" ] && echo "    - MariaDB: $DB_IMAGE"

        REDIS_IMAGE=$(grep "image: redis" "$PROJECT_ROOT/docker-compose.yml" | awk '{print $2}')
        [ -n "$REDIS_IMAGE" ] && echo "    - Redis: $REDIS_IMAGE"

        NGINX_IMAGE=$(grep "image: nginx" "$PROJECT_ROOT/docker-compose.yml" | awk '{print $2}')
        [ -n "$NGINX_IMAGE" ] && echo "    - Nginx: $NGINX_IMAGE"
    fi
else
    echo -e "  ${RED}❌ Dockerfile non trouvé${NC}"
fi
echo ""

# 11. Vérification Composer
echo -e "${BLUE}📦 Configuration Composer:${NC}"
echo "----------------------------"

if [ -f "$PROJECT_ROOT/composer.json" ]; then
    # PHP version requirement
    PHP_REQ=$(grep '"php":' "$PROJECT_ROOT/composer.json" | head -n 1 | sed 's/.*"php": "\([^"]*\)".*/\1/')
    echo "  • PHP requis dans composer.json: $PHP_REQ"

    if echo "$PHP_REQ" | grep -q "8.4"; then
        echo -e "    ${YELLOW}→ À mettre à jour: \"php\": \">=8.5\"${NC}"
    elif echo "$PHP_REQ" | grep -q "8.5"; then
        echo -e "    ${GREEN}✅ Déjà configuré pour PHP 8.5${NC}"
    fi

    # Symfony version requirement
    SYMFONY_REQ=$(grep '"symfony/framework-bundle":' "$PROJECT_ROOT/composer.json" | sed 's/.*"symfony\/framework-bundle": "\([^"]*\)".*/\1/')
    echo "  • Symfony requis: $SYMFONY_REQ"

    if echo "$SYMFONY_REQ" | grep -q "7\."; then
        echo -e "    ${YELLOW}→ À mettre à jour: \"symfony/*\": \"8.0.*\"${NC}"
    elif echo "$SYMFONY_REQ" | grep -q "8\."; then
        echo -e "    ${GREEN}✅ Déjà configuré pour Symfony 8${NC}"
    fi

    # Composer.lock age
    if [ -f "$PROJECT_ROOT/composer.lock" ]; then
        LOCK_AGE=$(find "$PROJECT_ROOT/composer.lock" -mtime +30 2>/dev/null | wc -l | tr -d ' ')
        if [ "$LOCK_AGE" -gt 0 ]; then
            LOCK_DATE=$(stat -f "%Sm" -t "%Y-%m-%d" "$PROJECT_ROOT/composer.lock" 2>/dev/null || stat -c "%y" "$PROJECT_ROOT/composer.lock" 2>/dev/null | cut -d' ' -f1)
            echo -e "  ${YELLOW}⚠️  composer.lock modifié le: $LOCK_DATE (>30 jours)${NC}"
            echo "    → Recommandé: composer update"
        else
            echo -e "  ${GREEN}✅${NC} composer.lock récent (<30 jours)"
        fi
    else
        echo -e "  ${RED}❌ composer.lock manquant${NC}"
    fi
else
    echo -e "  ${RED}❌ composer.json non trouvé${NC}"
fi
echo ""

# 12. Outils de qualité de code
echo -e "${BLUE}🔧 Outils de vérification:${NC}"
echo "----------------------------"

# PHPStan
if [ -f "$PROJECT_ROOT/phpstan.neon" ]; then
    PHPSTAN_VERSION=$(composer show phpstan/phpstan 2>/dev/null | grep "^versions" | awk '{print $3}' | tr -d '*' | tr -d ' ')
    PHPSTAN_LEVEL=$(grep "level:" "$PROJECT_ROOT/phpstan.neon" | head -n 1 | awk '{print $2}')
    echo "  • PHPStan: ${PHPSTAN_VERSION:-non installé} (niveau $PHPSTAN_LEVEL)"

    # Vérifier compatibilité PHP 8.5
    if [ -n "$PHPSTAN_VERSION" ]; then
        PHPSTAN_MAJOR=$(echo "$PHPSTAN_VERSION" | cut -d'.' -f1)
        if [ "$PHPSTAN_MAJOR" -lt 2 ] 2>/dev/null; then
            echo -e "    ${YELLOW}⚠️  PHPStan <2.0 peut avoir des problèmes avec PHP 8.5${NC}"
        else
            echo -e "    ${GREEN}✅ Version compatible${NC}"
        fi
    fi

    # Extensions PHPStan
    if grep -q "phpstan-doctrine" "$PROJECT_ROOT/composer.json"; then
        PHPSTAN_DOCTRINE=$(composer show phpstan/phpstan-doctrine 2>/dev/null | grep "^versions" | awk '{print $3}' | tr -d '*' | tr -d ' ')
        echo "    - phpstan-doctrine: ${PHPSTAN_DOCTRINE:-non installé}"
    fi
    if grep -q "phpstan-symfony" "$PROJECT_ROOT/composer.json"; then
        PHPSTAN_SYMFONY=$(composer show phpstan/phpstan-symfony 2>/dev/null | grep "^versions" | awk '{print $3}' | tr -d '*' | tr -d ' ')
        echo "    - phpstan-symfony: ${PHPSTAN_SYMFONY:-non installé}"
    fi
else
    echo -e "  ${YELLOW}⚠️  phpstan.neon non trouvé${NC}"
fi

# PHP CS Fixer
if [ -f "$PROJECT_ROOT/.php-cs-fixer.dist.php" ]; then
    PHPCS_VERSION=$(composer show friendsofphp/php-cs-fixer 2>/dev/null | grep "^versions" | awk '{print $3}' | tr -d '*' | tr -d ' ')
    echo "  • PHP-CS-Fixer: ${PHPCS_VERSION:-non installé}"

    # Vérifier la version minimale
    if [ -n "$PHPCS_VERSION" ]; then
        PHPCS_MAJOR=$(echo "$PHPCS_VERSION" | cut -d'.' -f1)
        if [ "$PHPCS_MAJOR" -ge 3 ] 2>/dev/null; then
            echo -e "    ${GREEN}✅ Version 3.x compatible PHP 8.5${NC}"
        else
            echo -e "    ${YELLOW}⚠️  Mise à jour recommandée vers 3.x${NC}"
        fi
    fi
else
    echo -e "  ${YELLOW}⚠️  .php-cs-fixer.dist.php non trouvé${NC}"
fi

# PHPMD
if [ -f "$PROJECT_ROOT/phpmd.xml" ]; then
    PHPMD_VERSION=$(composer show phpmd/phpmd 2>/dev/null | grep "^versions" | awk '{print $3}' | tr -d '*' | tr -d ' ')
    echo "  • PHPMD: ${PHPMD_VERSION:-non installé}"

    if [ -n "$PHPMD_VERSION" ]; then
        PHPMD_MAJOR=$(echo "$PHPMD_VERSION" | cut -d'.' -f1)
        if [ "$PHPMD_MAJOR" -ge 2 ] 2>/dev/null; then
            echo -e "    ${GREEN}✅ Version 2.x compatible${NC}"
        fi
    fi
else
    echo -e "  ${YELLOW}⚠️  phpmd.xml non trouvé${NC}"
fi

# PHPUnit
PHPUNIT_VERSION=$(composer show phpunit/phpunit 2>/dev/null | grep "^versions" | awk '{print $3}' | tr -d '*' | tr -d ' ')
if [ -n "$PHPUNIT_VERSION" ]; then
    echo "  • PHPUnit: $PHPUNIT_VERSION"

    PHPUNIT_MAJOR=$(echo "$PHPUNIT_VERSION" | cut -d'.' -f1)
    if [ "$PHPUNIT_MAJOR" -ge 10 ] 2>/dev/null; then
        echo -e "    ${GREEN}✅ Version 10+ compatible PHP 8.5${NC}"
    else
        echo -e "    ${YELLOW}⚠️  PHPUnit <10 peut nécessiter une mise à jour${NC}"
    fi
fi

# Symfony Panther (E2E)
if grep -q "symfony/panther" "$PROJECT_ROOT/composer.json"; then
    PANTHER_VERSION=$(composer show symfony/panther 2>/dev/null | grep "^versions" | awk '{print $3}' | tr -d '*' | tr -d ' ')
    echo "  • Symfony Panther: ${PANTHER_VERSION:-non installé}"
fi

echo ""

# 13. Scripts composer
echo -e "${BLUE}⚙️  Scripts Composer personnalisés:${NC}"
echo "----------------------------"

if [ -f "$PROJECT_ROOT/composer.json" ]; then
    # Liste des scripts de qualité
    HAS_SCRIPTS=false

    if grep -q '"check-code"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer check-code"
        HAS_SCRIPTS=true
    fi
    if grep -q '"test"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer test"
        HAS_SCRIPTS=true
    fi
    if grep -q '"phpstan"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer phpstan"
        HAS_SCRIPTS=true
    fi
    if grep -q '"phpcsfixer"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer phpcsfixer"
        HAS_SCRIPTS=true
    fi
    if grep -q '"phpmd"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer phpmd"
        HAS_SCRIPTS=true
    fi
    if grep -q '"test-unit"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer test-unit"
        HAS_SCRIPTS=true
    fi
    if grep -q '"test-functional"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer test-functional"
        HAS_SCRIPTS=true
    fi
    if grep -q '"test-api"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer test-api"
        HAS_SCRIPTS=true
    fi
    if grep -q '"test-e2e"' "$PROJECT_ROOT/composer.json"; then
        echo "  ✅ composer test-e2e"
        HAS_SCRIPTS=true
    fi

    if [ "$HAS_SCRIPTS" = true ]; then
        echo ""
        echo -e "  ${CYAN}💡 À tester après migration:${NC}"
        echo "    1. composer check-code"
        echo "    2. composer test"
        echo "    3. Vérifier les logs d'erreur"
    else
        echo "  Aucun script de qualité détecté"
    fi
fi
echo ""

# 14. Configuration PHPStan pour PHP 8.5
echo -e "${BLUE}🔍 Configuration PHPStan:${NC}"
echo "----------------------------"

if [ -f "$PROJECT_ROOT/phpstan.neon" ]; then
    # Vérifier si phpVersion est défini
    if grep -q "phpVersion:" "$PROJECT_ROOT/phpstan.neon"; then
        PHP_VERSION_PHPSTAN=$(grep "phpVersion:" "$PROJECT_ROOT/phpstan.neon" | awk '{print $2}')
        echo "  • phpVersion configuré: $PHP_VERSION_PHPSTAN"

        if echo "$PHP_VERSION_PHPSTAN" | grep -q "80[45]"; then
            echo -e "    ${YELLOW}→ À mettre à jour: phpVersion: 80500${NC}"
        else
            echo -e "    ${GREEN}✅ Configuration actuelle OK${NC}"
        fi
    else
        echo -e "  ${YELLOW}⚠️  phpVersion non configuré (utilise version système)${NC}"
        echo "    Ajouter dans phpstan.neon:"
        echo "    parameters:"
        echo "      phpVersion: 80500  # PHP 8.5"
    fi

    # Vérifier ignoreErrors
    if grep -q "ignoreErrors:" "$PROJECT_ROOT/phpstan.neon"; then
        IGNORE_COUNT=$(grep -A 100 "ignoreErrors:" "$PROJECT_ROOT/phpstan.neon" | grep -c "^\s*-" || echo 0)
        if [ "$IGNORE_COUNT" -gt 0 ]; then
            echo -e "  ${YELLOW}⚠️  $IGNORE_COUNT erreurs ignorées dans la configuration${NC}"
            echo "    → Vérifier si toujours nécessaires après migration"
        fi
    fi
else
    echo -e "  ${YELLOW}⚠️  Fichier de configuration non trouvé${NC}"
fi
echo ""

# 15. Suggestion de prochaines étapes
echo -e "${BLUE}📋 Prochaines étapes recommandées:${NC}"
echo "----------------------------"
echo "1. Attendre la sortie de PHP 8.5 et Symfony 8.0 (novembre 2025)"
echo "2. Lire les changelogs officiels:"
echo "   • https://www.php.net/releases/8.5/en.php"
echo "   • https://symfony.com/releases/8.0"
echo "3. Créer une branche de test: git checkout -b feature/php85-symfony8"
echo "4. Mettre à jour Dockerfile: FROM php:8.5-fpm-alpine"
echo "5. Mettre à jour composer.json:"
echo "   • \"php\": \">=8.5\""
echo "   • \"symfony/*\": \"8.0.*\""
echo "6. Mettre à jour les outils: composer update --dev"
echo "7. Lancer les tests: composer test"
echo "8. Vérifier qualité: composer check-code"
echo "9. Consulter: docs/migration-php85-symfony8.md"
echo ""

# 16. Résumé
echo -e "${BLUE}📊 Résumé:${NC}"
echo "=========================================="
echo -e "Status: ${YELLOW}⏳ En attente de PHP 8.5 et Symfony 8${NC}"
echo "Documentation:"
echo "  • Guide complet: docs/migration-php85-symfony8.md"
echo "  • Rapport d'analyse: docs/migration-analysis-report.md"
echo "Date prévue: Novembre 2025"
echo ""
echo "Prochaine action recommandée:"
echo "  ./scripts/check-migration-compatibility.sh > migration-check-$(date +%Y%m%d).txt"
echo "=========================================="
