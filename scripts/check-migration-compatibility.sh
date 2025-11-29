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
NC='\033[0m' # No Color

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

# 10. Suggestion de prochaines étapes
echo -e "${BLUE}📋 Prochaines étapes recommandées:${NC}"
echo "----------------------------"
echo "1. Attendre la sortie de PHP 8.5 et Symfony 8.0 (novembre 2025)"
echo "2. Lire les changelogs officiels:"
echo "   • https://www.php.net/releases/8.5/en.php"
echo "   • https://symfony.com/releases/8.0"
echo "3. Créer une branche de test: git checkout -b feature/php85-symfony8"
echo "4. Mettre à jour Dockerfile: php:8.5-fpm-alpine"
echo "5. Mettre à jour composer.json: symfony/*:8.0.*"
echo "6. Lancer les tests: composer test"
echo "7. Consulter: docs/migration-php85-symfony8.md"
echo ""

# 11. Résumé
echo -e "${BLUE}📊 Résumé:${NC}"
echo "=========================================="
echo -e "Status: ${YELLOW}⏳ En attente de PHP 8.5 et Symfony 8${NC}"
echo "Documentation: docs/migration-php85-symfony8.md"
echo "Date prévue: Novembre 2025"
echo "=========================================="
