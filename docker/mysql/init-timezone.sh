#!/bin/bash
# Script to load MySQL timezone tables
# This allows CONVERT_TZ() to work with named timezones instead of just offsets

set -e

echo "Loading MySQL timezone tables..."

# Load timezone data from system timezone info
mysql_tzinfo_to_sql /usr/share/zoneinfo | docker compose exec -T db mysql -uroot -proot mysql

echo "✅ Timezone tables loaded successfully!"
echo "You can now use named timezones like 'Europe/Paris' in SQL queries."
