#!/bin/bash
#
# Script de correction des migrations en production
# À exécuter via Railway CLI ou directement sur le serveur
#
# Ce script :
# 1. Nettoie l'historique des migrations
# 2. Exécute la migration idempotente pour ajouter les colonnes manquantes
# 3. Marque toutes les migrations comme exécutées
#

set -e  # Arrêt en cas d'erreur

echo "=========================================="
echo "Correction des migrations en production"
echo "=========================================="
echo ""

# Étape 1 : Sauvegarder l'état actuel
echo "1️⃣  Sauvegarde de l'état actuel des migrations..."
php bin/console doctrine:migrations:status > migrations-status-before.txt
echo "✓ Sauvegarde dans migrations-status-before.txt"
echo ""

# Étape 2 : Nettoyer la table doctrine_migration_versions
echo "2️⃣  Nettoyage de la table doctrine_migration_versions..."
php bin/console dbal:run-sql "TRUNCATE TABLE doctrine_migration_versions"
echo "✓ Table nettoyée"
echo ""

# Étape 3 : Exécuter la migration idempotente
echo "3️⃣  Exécution de la migration idempotente (Version20260110081458)..."
php bin/console doctrine:migrations:execute --up DoctrineMigrations\\Version20260110081458 --no-interaction
echo "✓ Migration idempotente exécutée"
echo ""

# Étape 4 : Marquer toutes les migrations comme exécutées
echo "4️⃣  Marquage de toutes les migrations comme exécutées..."
php bin/console doctrine:migrations:version --add --all --no-interaction
echo "✓ Toutes les migrations marquées comme exécutées"
echo ""

# Étape 5 : Vérifier l'état final
echo "5️⃣  Vérification de l'état final..."
php bin/console doctrine:migrations:status
echo ""

echo "=========================================="
echo "✅ Correction terminée avec succès !"
echo "=========================================="
echo ""
echo "Prochaines étapes :"
echo "  - Vérifier que l'application fonctionne correctement"
echo "  - Tester la création/modification d'articles de blog"
echo "  - Les prochaines migrations fonctionneront normalement"
