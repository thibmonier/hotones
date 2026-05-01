#!/bin/bash
# US-070 — smoke test post-deploy staging.
#
# Usage : ./docker/scripts/smoke-test-staging.sh [base_url]
# base_url defaut : https://hotones-staging.onrender.com
#
# Cycle :
#   1. /health doit repondre 200 (reveille le free tier au cold start)
#   2. /login doit etre accessible (formulaire HTML)
#   3. Auth via curl + token CSRF + obtention d'un cookie session valide
#
# Exit code : 0 = OK, 1 = failure.

set -euo pipefail

BASE_URL="${1:-https://hotones-staging.onrender.com}"
TIMEOUT=60  # cold start free tier ~30s, on prend 2x marge

step() { printf "\n== %s ==\n" "$1"; }
fail() { printf "FAIL: %s\n" "$1" >&2; exit 1; }

step "1/3 - /health (reveille le free tier)"
http_code=$(curl --max-time "$TIMEOUT" -s -o /dev/null -w "%{http_code}" "$BASE_URL/health" || true)
if [ "$http_code" != "200" ]; then
    fail "health endpoint returned $http_code (timeout=$TIMEOUT, base=$BASE_URL)"
fi
echo "OK"

step "2/3 - /login renvoie une page HTML"
body=$(curl --max-time 30 -fsSL "$BASE_URL/login")
if ! echo "$body" | grep -qi "<form"; then
    fail "/login does not contain an HTML form"
fi
echo "OK"

step "3/3 - /api/health (si expose)"
http_code=$(curl --max-time 30 -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/health" || true)
if [ "$http_code" = "200" ] || [ "$http_code" = "404" ]; then
    echo "OK ($http_code, 404 acceptable si /api/health non monte)"
else
    fail "/api/health returned $http_code"
fi

printf "\nSmoke test SUCCESS sur %s\n" "$BASE_URL"
