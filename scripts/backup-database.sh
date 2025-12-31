#!/bin/bash
#
# Database Backup Script for HotOnes
#
# Usage: ./scripts/backup-database.sh [backup_name]
#
# This script creates a MySQL dump of the hotones database before
# running migrations. Backups are stored in backups/ directory with
# timestamps.
#
# To restore a backup:
#   docker compose exec db mysql -u symfony -psymfony hotones < backups/backup_TIMESTAMP.sql
#

set -e  # Exit on error

# Configuration
BACKUP_DIR="backups"
DB_CONTAINER="hotones_db"
DB_NAME="hotones"
DB_USER="symfony"
DB_PASS="symfony"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Generate backup filename
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="${1:-migration_before}"
BACKUP_FILE="${BACKUP_DIR}/${BACKUP_NAME}_${TIMESTAMP}.sql"

echo "🗄️  HotOnes Database Backup"
echo "================================"
echo "Database: $DB_NAME"
echo "Backup file: $BACKUP_FILE"
echo ""

# Check if Docker container is running
if ! docker compose ps | grep -q "$DB_CONTAINER.*Up"; then
    echo "❌ Error: Database container is not running!"
    echo "   Start it with: docker compose up -d"
    exit 1
fi

# Create backup
echo "📦 Creating backup..."
docker compose exec -T db mysqldump \
    -u "$DB_USER" \
    -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_FILE"

# Check if backup was successful
if [ -f "$BACKUP_FILE" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo "✅ Backup created successfully!"
    echo "   File: $BACKUP_FILE"
    echo "   Size: $BACKUP_SIZE"
    echo ""
    echo "📋 To restore this backup:"
    echo "   docker compose exec -T db mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE"
    echo ""
    echo "📋 To restore and switch to main branch:"
    echo "   1. git checkout main"
    echo "   2. docker compose exec -T db mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE"
    echo "   3. docker compose exec app php bin/console doctrine:migrations:sync-metadata-storage"
else
    echo "❌ Error: Backup failed!"
    exit 1
fi

# Keep only last 10 backups (optional cleanup)
BACKUP_COUNT=$(ls -1 "$BACKUP_DIR"/*.sql 2>/dev/null | wc -l)
if [ "$BACKUP_COUNT" -gt 10 ]; then
    echo "🧹 Cleaning old backups (keeping last 10)..."
    ls -t "$BACKUP_DIR"/*.sql | tail -n +11 | xargs rm -f
fi

exit 0
