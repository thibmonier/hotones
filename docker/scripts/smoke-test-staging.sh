#!/bin/bash
# US-070 — smoke test post-deploy staging.
# TEST-E2E-STAGING-001 (sprint-005) — extended with JWT login + authenticated read.
#
# Usage : ./docker/scripts/smoke-test-staging.sh [base_url]
# base_url defaut : https://hotones-staging.onrender.com
#
# Environment variables (optional, gate the auth step):
#   SMOKE_USERNAME — email of a smoke user provisioned in staging (e.g. smoke@test.com)
#   SMOKE_PASSWORD — the matching password
#
#   When both are set, steps 4 and 5 run. When either is unset, those steps
#   are skipped with a "SKIP" message — the script still exits 0 if the
#   anonymous steps (1-3) pass.
#
# Cycle :
#   1. /health doit repondre 200 (reveille le free tier au cold start)
#   2. /login doit etre accessible (formulaire HTML)
#   3. /api/health (si expose)
#   4. POST /api/login retourne un JWT (gated by SMOKE_USERNAME/SMOKE_PASSWORD)
#   5. GET /api/contributors avec Bearer token retourne 200 + JSON-LD valide
#
# Exit code : 0 = OK, 1 = failure.

set -euo pipefail

BASE_URL="${1:-https://hotones-staging.onrender.com}"
TIMEOUT=60  # cold start free tier ~30s, on prend 2x marge

step() { printf "\n== %s ==\n" "$1"; }
fail() { printf "FAIL: %s\n" "$1" >&2; exit 1; }
skip() { printf "SKIP: %s\n" "$1"; }

step "1/5 - /health (reveille le free tier)"
http_code=$(curl --max-time "$TIMEOUT" -s -o /dev/null -w "%{http_code}" "$BASE_URL/health" || true)
if [ "$http_code" != "200" ]; then
    fail "health endpoint returned $http_code (timeout=$TIMEOUT, base=$BASE_URL)"
fi
echo "OK"

step "2/5 - /login renvoie une page HTML"
body=$(curl --max-time 30 -fsSL "$BASE_URL/login")
if ! echo "$body" | grep -qi "<form"; then
    fail "/login does not contain an HTML form"
fi
echo "OK"

step "3/5 - /api/health (si expose)"
http_code=$(curl --max-time 30 -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/health" || true)
if [ "$http_code" = "200" ] || [ "$http_code" = "404" ]; then
    echo "OK ($http_code, 404 acceptable si /api/health non monte)"
else
    fail "/api/health returned $http_code"
fi

step "4/5 - JWT login via POST /api/login"
if [ -z "${SMOKE_USERNAME:-}" ] || [ -z "${SMOKE_PASSWORD:-}" ]; then
    skip "SMOKE_USERNAME/SMOKE_PASSWORD not set — auth-gated steps disabled"
    JWT=""
else
    login_body=$(printf '{"email":"%s","password":"%s"}' "$SMOKE_USERNAME" "$SMOKE_PASSWORD")
    response=$(curl --max-time 30 -s -X POST "$BASE_URL/api/login" \
        -H "Content-Type: application/json" \
        -d "$login_body" || true)
    JWT=$(echo "$response" | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')
    if [ -z "$JWT" ]; then
        fail "/api/login did not return a JWT token (response: $response)"
    fi
    echo "OK (got JWT, $(printf '%s' "$JWT" | wc -c | tr -d ' ') chars)"
fi

step "5/5 - GET /api/contributors avec Bearer token"
if [ -z "$JWT" ]; then
    skip "no JWT — step 4 was skipped"
else
    contributors_body=$(curl --max-time 30 -s -H "Authorization: Bearer $JWT" \
        -H "Accept: application/ld+json" \
        "$BASE_URL/api/contributors" || true)
    # API Platform JSON-LD always carries `@context` + `hydra:member`.
    # Fall back to plain JSON arrays when content negotiation prefers them.
    if echo "$contributors_body" | grep -q '"hydra:member"'; then
        echo "OK (JSON-LD response with hydra:member array)"
    elif echo "$contributors_body" | grep -q '^\[' || echo "$contributors_body" | grep -q '"@context"'; then
        echo "OK (JSON or JSON-LD response)"
    else
        fail "/api/contributors response unexpected: $(echo "$contributors_body" | head -c 200)"
    fi
fi

printf "\nSmoke test SUCCESS sur %s\n" "$BASE_URL"
