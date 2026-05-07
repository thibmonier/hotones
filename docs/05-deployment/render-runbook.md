# Runbook — Déploiement Render

> **US-090** (sprint-014) — Runbook de déploiement et dépannage Render.

## Architecture cloud

| Composant | Provider | Notes |
|---|---|---|
| **Web (PHP-FPM + nginx)** | Render | Docker build, region Frankfurt, plan starter |
| **Database (MariaDB/MySQL)** | Railway | Externe, DSN injecté via env var `DATABASE_URL` |
| **Redis (cache + messenger)** | Render | Service géré, plan starter |
| **CDN / DNS** | Cloudflare | Devant Render |
| **Object storage** | Cloudflare R2 | `*.r2.dev` (uploads + backups) |

**URL prod** : <https://hotones.onrender.com/>

---

## Bug fixé sprint-014 (US-090)

### Symptôme

`GET /health` retournait `<?php http_response_code(200); echo "OK";` en
texte brut (Content-Type: `application/octet-stream`) au lieu d'exécuter PHP.

### Cause

`Dockerfile` ligne 138 (avant fix) créait un fichier statique :

```dockerfile
RUN echo '<?php http_response_code(200); echo "OK";' > /var/www/html/public/health
```

nginx `try_files $uri /index.php` matche d'abord le fichier statique
`public/health` → sert le PHP **comme texte**. Le `HealthCheckController`
Symfony (route `/health`) n'était jamais atteint.

### Fix

Suppression du `RUN echo` dans Dockerfile. Symfony route `/health` prend
le relais via fallback `/index.php`. Le controller retourne du JSON
`{status, db, redis, version}` avec vraies vérifications.

---

## Déploiement standard

### Auto-deploy via push main

Render écoute la branche `main`. Tout merge déclenche automatiquement :

1. `git pull origin main`
2. `docker build -f Dockerfile`
3. Health check sur `/health` (5 min timeout)
4. Switch trafic → nouveau container
5. Drain ancien container

### Healthcheck post-deploy

```bash
# Réveille free tier si nécessaire
curl -sI https://hotones.onrender.com/

# Vérifie /health (JSON)
curl -s https://hotones.onrender.com/health | jq .

# Attendu :
# {
#   "status": "ok",
#   "db": "ok",
#   "redis": "ok",
#   "version": "..."
# }
```

---

## Variables d'environnement

| Variable | Source | Notes |
|---|---|---|
| `APP_ENV` | render.yaml | `prod` |
| `APP_SECRET` | render.yaml | `generateValue: true` (auto) |
| `DATABASE_URL` | Manuel dashboard | DSN Railway MySQL |
| `REDIS_URL` | Render service | Auto-injecté depuis `hotones-redis` |
| `MESSENGER_TRANSPORT_DSN` | Render service | Auto-injecté (Redis) |
| `JWT_SECRET_KEY` | render.yaml | `%kernel.project_dir%/config/jwt/private.pem` |
| `JWT_PUBLIC_KEY` | render.yaml | `%kernel.project_dir%/config/jwt/public.pem` |
| `JWT_PASSPHRASE` | Manuel dashboard | Secret JWT |
| `MAILER_DSN` | Manuel dashboard | SMTP provider DSN |
| `OPENAI_API_KEY` | Manuel dashboard | Optionnel (AI features) |
| `ANTHROPIC_API_KEY` | Manuel dashboard | Optionnel (AI features) |
| `TZ` | render.yaml | `Europe/Paris` |
| `DEFAULT_URI` | render.yaml | `https://hotones.onrender.com` |
| `AVATARS_DIRECTORY` | render.yaml | `/var/www/html/var/uploads/avatars` (disque persistant) |

---

## Disque persistant

| Mount | Path | Taille | Usage |
|---|---|---:|---|
| `hotones-storage` | `/var/www/html/var` | 1 GB | uploads (avatars, logos), sessions, cache |

**⚠️ Attention** : `cache/prod` est sur ce volume → garder propre via
warmup post-deploy. Voir `docker/scripts/start-render.sh`.

---

## Dépannage

### `GET /health` retourne 502 / 503

1. Vérifier service Render dashboard : statut `running` ?
2. Vérifier logs Render dernier deploy : `docker run` errors ?
3. Vérifier `DATABASE_URL` accessible : `bin/console dbal:run-sql "SELECT 1"`
4. Vérifier Redis : `bin/console redis:ping`

### `GET /health` retourne 200 mais contenu = `<?php` raw

→ Static file `public/health` recréé par mistake dans Dockerfile.
Vérifier que le fix US-090 (suppression du `RUN echo`) est bien sur main.

### Database connection failed (Railway down)

1. Vérifier dashboard Railway : MySQL service running ?
2. Vérifier quota Railway : crédit mensuel restant ?
3. Failover : si Railway down > 1h, considérer migration PostgreSQL Render
   (cf. `deployment-render-postgres.md` — guide migration MySQL → PG)

### Free tier cold start (50s)

Render free tier endort un service après 15 min inactivité. Premier
request post-réveil = 30-50s. Mitigation :
- Plan **starter** ($7/mois) = pas de cold start
- OU keep-alive cron externe (UptimeRobot) ping `/health` toutes 5 min

### Build Docker timeout

Render limite build à 30 min. Si dépassement :
1. Vérifier `Dockerfile` : multi-stage builds optimisés ?
2. Vérifier `.dockerignore` : exclure `var/`, `vendor/`, `node_modules/`
3. BuildKit cache mounts (déjà actif Dockerfile ligne 1)

### Storage disk full

```bash
# Render shell (via dashboard)
df -h /var/www/html/var
du -sh /var/www/html/var/* | sort -h
# Cleanup : sessions vieilles, cache prod, uploads orphelins
```

---

## Smoke test post-deploy

```bash
# Script : docker/scripts/smoke-test-staging.sh
./docker/scripts/smoke-test-staging.sh https://hotones.onrender.com
```

Tests :
1. `GET /health` → 200
2. `GET /` → 200 (homepage)
3. `GET /api/health` → 200 ou 404 (acceptable si non monté)

---

## Métriques cibles

| Métrique | Cible | Action si dépassée |
|---|---|---|
| Build duration | < 10 min | Investigate Dockerfile |
| Deploy duration | < 15 min | Render support |
| Cold start (free tier) | < 60s | Switch starter |
| `/health` latence | < 500ms | DB query slow ? |
| Memory usage | < 80% plan | Upgrade ou profile leaks |

---

## Liens

- [Render dashboard](https://dashboard.render.com/)
- [Railway dashboard](https://railway.app/)
- [render.yaml blueprint](../../render.yaml)
- [render.staging.yaml](../../render.staging.yaml)
- [Dockerfile](../../Dockerfile)
- [docker/scripts/start-render.sh](../../docker/scripts/start-render.sh)
- [docker/scripts/smoke-test-staging.sh](../../docker/scripts/smoke-test-staging.sh)
