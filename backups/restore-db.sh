#!/bin/bash

# Script de restauration de la BDD pour feature/lot-23-multi-tenant

set -e

echo "🔄 Restauration de la base de données - feature/lot-23-multi-tenant"
echo ""

# Trouver le backup le plus récent
BACKUP_FILE=$(ls -t backups/db-backup-feature-lot-23-*.sql 2>/dev/null | head -n1)

if [ -z "$BACKUP_FILE" ]; then
    echo "❌ Erreur : Aucun fichier de backup trouvé dans backups/"
    echo "   Recherche : backups/db-backup-feature-lot-23-*.sql"
    exit 1
fi

echo "📦 Fichier de backup trouvé : $BACKUP_FILE"
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
echo "   Taille : $BACKUP_SIZE"
echo ""

# Demander confirmation
read -p "⚠️  Voulez-vous restaurer ce backup ? Cela écrasera la BDD actuelle (y/N) : " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Restauration annulée"
    exit 0
fi

echo ""
echo "🗑️  Suppression de la base de données actuelle..."
docker compose exec -T db sh -c 'mariadb -u symfony -psymfony -e "DROP DATABASE IF EXISTS hotones; CREATE DATABASE hotones CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"'

echo "📥 Restauration du backup..."
docker compose exec -T db sh -c 'mariadb -u symfony -psymfony hotones' < "$BACKUP_FILE"

echo ""
echo "✅ Base de données restaurée avec succès !"
echo ""
echo "🔍 Vérification de la structure..."
docker compose exec app php bin/console doctrine:schema:validate

echo ""
echo "🧹 Nettoyage du cache..."
docker compose exec app php bin/console cache:clear

echo ""
echo "✅ Restauration terminée !"
echo ""
echo "💡 Vous pouvez maintenant continuer à travailler sur la branche multi-tenant"
