#!/bin/bash
#
# Database Restore Script for HotOnes
#
# Usage: ./scripts/restore-database.sh <backup_file>
#
# Example: ./scripts/restore-database.sh backups/migration_before_20250101_120000.sql
#

set -e  # Exit on error

# Configuration
DB_CONTAINER="hotones_db"
DB_NAME="hotones"
DB_USER="symfony"
DB_PASS="symfony"

# Check arguments
if [ -z "$1" ]; then
    echo "❌ Error: No backup file specified!"
    echo ""
    echo "Usage: $0 <backup_file>"
    echo ""
    echo "Available backups:"
    ls -lh backups/*.sql 2>/dev/null || echo "  No backups found"
    exit 1
fi

BACKUP_FILE="$1"

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Error: Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "🔄 HotOnes Database Restore"
echo "================================"
echo "Database: $DB_NAME"
echo "Backup file: $BACKUP_FILE"
echo ""

# Confirmation prompt
read -p "⚠️  This will REPLACE the current database. Continue? (y/N) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Restore cancelled"
    exit 1
fi

# Check if Docker container is running
if ! docker compose ps | grep -q "$DB_CONTAINER.*Up"; then
    echo "❌ Error: Database container is not running!"
    echo "   Start it with: docker compose up -d"
    exit 1
fi

# Drop and recreate database
echo "🗑️  Dropping existing database..."
docker compose exec -T db mysql -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restore backup
echo "📥 Restoring backup..."
docker compose exec -T db mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_FILE"

# Sync migration metadata
echo "🔄 Syncing migration metadata..."
docker compose exec app php bin/console doctrine:migrations:sync-metadata-storage --quiet

echo ""
echo "✅ Database restored successfully!"
echo ""
echo "📋 Next steps:"
echo "   1. Verify application works: http://localhost:8080"
echo "   2. Check migration status: docker compose exec app php bin/console doctrine:migrations:status"
echo ""

exit 0
