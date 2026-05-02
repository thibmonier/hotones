# Tâches — TEST-E2E-STAGING-001

## Informations

- **Story Points** : 3
- **MoSCoW** : Should
- **Origine** : sprint-004 review candidate (extension OPS-009)
- **Total estimé** : 7h

## Résumé

`docker/scripts/smoke-test-staging.sh` (US-070) vérifie que `/health` et `/login` répondent. Trop léger pour détecter une régression métier (mauvaise migration, contrainte FK cassée, JWT brisé). Cette story étend le smoke avec un cycle login JWT → lecture → écriture → cleanup.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TES-01 | [TEST] | Étendre `smoke-test-staging.sh` : POST `/api/login_check` → JWT | 2h | - | 🔲 |
| T-TES-02 | [TEST] | Read assertion : GET `/api/projects` (200 + JSON shape attendue) | 2h | T-TES-01 | 🔲 |
| T-TES-03 | [TEST] | Write assertion : POST + DELETE sur une ressource jetable (ex: NoteService) | 2h | T-TES-02 | 🔲 |
| T-TES-04 | [DOC] | Mettre à jour `docs/04-development/staging-smoke.md` | 1h | T-TES-03 | 🔲 |

## Détail des tâches

### T-TES-01 — Login JWT

Étendre le bash :

```bash
step "4/X - login JWT"
JWT=$(curl --max-time 30 -s -X POST "$BASE_URL/api/login_check" \
    -H 'Content-Type: application/json' \
    -d "{\"username\":\"$SMOKE_USERNAME\",\"password\":\"$SMOKE_PASSWORD\"}" \
    | jq -r '.token // empty')
[ -n "$JWT" ] || fail "JWT login failed"
echo "OK"
```

Variables d'env requises : `SMOKE_USERNAME`, `SMOKE_PASSWORD`. Configurer un user dédié `smoke@test.com` dans staging.

### T-TES-02 — Read assertion

```bash
step "5/X - read /api/projects"
http_code=$(curl --max-time 30 -s -o /tmp/projects.json -w "%{http_code}" \
    -H "Authorization: Bearer $JWT" "$BASE_URL/api/projects")
[ "$http_code" = "200" ] || fail "/api/projects $http_code"

# JSON shape : doit avoir 'hydra:member' (API Platform JSONLD)
jq -e '."hydra:member" | type == "array"' /tmp/projects.json > /dev/null \
    || fail "/api/projects: missing hydra:member array"
echo "OK"
```

### T-TES-03 — Write assertion

Choisir une entité légère pour POST/DELETE (NoteService ou similar). Si pas dispo, créer une endpoint `/api/smoke/ping` qui POST → log → renvoie 201, et un DELETE qui retire.

```bash
step "6/X - write cycle"
created_id=$(curl --max-time 30 -s -X POST "$BASE_URL/api/notes" \
    -H "Authorization: Bearer $JWT" \
    -H 'Content-Type: application/ld+json' \
    -d '{"title":"smoke","content":"ping"}' \
    | jq -r '.id')
[ -n "$created_id" ] || fail "POST /api/notes failed"

curl --max-time 30 -s -X DELETE "$BASE_URL/api/notes/$created_id" \
    -H "Authorization: Bearer $JWT" \
    -o /dev/null -w "%{http_code}" | grep -q "204" || fail "DELETE /api/notes/$created_id"
echo "OK"
```

### T-TES-04 — Doc

`docs/04-development/staging-smoke.md` :
- Variables d'env requises (`SMOKE_USERNAME`, `SMOKE_PASSWORD`)
- Procédure pour créer le user smoke en staging
- Comment skipper si l'API n'est pas dispo localement (mode `--api-only-from-staging`)

## DoD

- [ ] `smoke-test-staging.sh https://hotones-staging.onrender.com` exécute les 6 étapes (1-3 existantes + 4-6 nouvelles)
- [ ] User smoke configuré dans staging
- [ ] Secrets GitHub (`SMOKE_USERNAME`, `SMOKE_PASSWORD`) wirés au workflow `staging-smoke-test.yml`
- [ ] Doc mise à jour
- [ ] Test manuel via `gh workflow run staging-smoke-test.yml -f wait_seconds=30` → tous étapes vertes

## Risques

- API Platform peut renvoyer du `application/json` au lieu de `application/ld+json` selon le content negotiation → ajuster le shape check
- Si `/api/login_check` n'est pas exposé, prévoir une étape équivalente via le form login web (récupérer le cookie session)
