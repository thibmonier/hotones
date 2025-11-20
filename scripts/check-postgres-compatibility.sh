#!/bin/bash
# Script de vérification de compatibilité PostgreSQL
# Scanne le code pour trouver les fonctions SQL spécifiques à MySQL

set -e

echo "========================================="
echo "Vérification compatibilité PostgreSQL"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

issues_found=0

# 1. Chercher DATE_FORMAT
echo "🔍 Recherche de DATE_FORMAT (MySQL)..."
if grep -rn "DATE_FORMAT" src/ 2>/dev/null; then
    echo -e "${YELLOW}⚠️  DATE_FORMAT trouvé - Remplacer par TO_CHAR() en PostgreSQL${NC}"
    issues_found=$((issues_found + 1))
else
    echo -e "${GREEN}✅ Aucun DATE_FORMAT trouvé${NC}"
fi
echo ""

# 2. Chercher YEAR(), MONTH(), WEEK()
echo "🔍 Recherche de YEAR(), MONTH(), WEEK()..."
if grep -rn "YEAR(\|MONTH(\|WEEK(" src/ 2>/dev/null; then
    echo -e "${YELLOW}⚠️  Fonctions temporelles MySQL trouvées - Utiliser EXTRACT() en PostgreSQL${NC}"
    echo "   Exemple: YEAR(date) → EXTRACT(YEAR FROM date)"
    issues_found=$((issues_found + 1))
else
    echo -e "${GREEN}✅ Aucune fonction temporelle MySQL trouvée${NC}"
fi
echo ""

# 3. Chercher IFNULL
echo "🔍 Recherche de IFNULL..."
if grep -rn "IFNULL" src/ 2>/dev/null; then
    echo -e "${YELLOW}⚠️  IFNULL trouvé - Utiliser COALESCE() en PostgreSQL${NC}"
    issues_found=$((issues_found + 1))
else
    echo -e "${GREEN}✅ Aucun IFNULL trouvé${NC}"
fi
echo ""

# 4. Chercher AUTO_INCREMENT dans annotations
echo "🔍 Recherche de AUTO_INCREMENT dans les entités..."
if grep -rn "AUTO_INCREMENT\|autoincrement" src/Entity/ 2>/dev/null; then
    echo -e "${YELLOW}⚠️  AUTO_INCREMENT trouvé - PostgreSQL utilise SERIAL/IDENTITY${NC}"
    issues_found=$((issues_found + 1))
else
    echo -e "${GREEN}✅ Pas d'AUTO_INCREMENT explicite (Doctrine gère automatiquement)${NC}"
fi
echo ""

# 5. Vérifier les types ENUM
echo "🔍 Recherche de types ENUM..."
if grep -rn "columnDefinition.*ENUM\|type.*enum" src/Entity/ 2>/dev/null; then
    echo -e "${YELLOW}⚠️  Type ENUM trouvé - PostgreSQL supporte ENUM mais différemment${NC}"
    issues_found=$((issues_found + 1))
else
    echo -e "${GREEN}✅ Aucun type ENUM trouvé${NC}"
fi
echo ""

# 6. Chercher LIMIT avec OFFSET MySQL-style
echo "🔍 Recherche de LIMIT ... OFFSET..."
if grep -rn "LIMIT.*," src/ 2>/dev/null; then
    echo -e "${YELLOW}⚠️  LIMIT MySQL-style trouvé - PostgreSQL utilise LIMIT ... OFFSET${NC}"
    echo "   MySQL: LIMIT 10, 20"
    echo "   PostgreSQL: LIMIT 20 OFFSET 10"
    issues_found=$((issues_found + 1))
else
    echo -e "${GREEN}✅ Pas de LIMIT MySQL-style (Doctrine QueryBuilder gère automatiquement)${NC}"
fi
echo ""

# 7. Vérifier les backticks
echo "🔍 Recherche de backticks MySQL..."
if grep -rn '`[a-zA-Z_]' src/ 2>/dev/null; then
    echo -e "${YELLOW}⚠️  Backticks MySQL trouvés - PostgreSQL utilise des guillemets doubles${NC}"
    issues_found=$((issues_found + 1))
else
    echo -e "${GREEN}✅ Aucun backtick trouvé${NC}"
fi
echo ""

# Résumé
echo "========================================="
if [ $issues_found -eq 0 ]; then
    echo -e "${GREEN}🎉 RÉSULTAT : Code compatible PostgreSQL !${NC}"
    echo ""
    echo "Votre code semble compatible. Vous pouvez procéder à la migration :"
    echo "1. Suivez le guide: docs/deployment-render-postgres.md"
    echo "2. Changez DATABASE_URL vers PostgreSQL"
    echo "3. Régénérez les migrations"
    echo "4. Testez !"
else
    echo -e "${YELLOW}⚠️  RÉSULTAT : $issues_found potentiel(s) problème(s) détecté(s)${NC}"
    echo ""
    echo "Actions recommandées :"
    echo "1. Corrigez les incompatibilités listées ci-dessus"
    echo "2. Consultez: docs/deployment-render-postgres.md"
    echo "3. Testez la migration en local d'abord"
fi
echo "========================================="
echo ""

# Statistiques
echo "📊 Statistiques du projet:"
echo "   Entités: $(find src/Entity -name "*.php" 2>/dev/null | wc -l | tr -d ' ')"
echo "   Repositories: $(find src/Repository -name "*.php" 2>/dev/null | wc -l | tr -d ' ')"
echo "   Migrations MySQL actuelles: $(find migrations -name "*.php" 2>/dev/null | wc -l | tr -d ' ')"
echo ""

exit 0
