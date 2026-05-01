# Environnement de staging (US-070)

> Origine : sprint-003 US-070, retro sprint-002 thème D (démo bloquée par absence d'env staging).

## Vue d'ensemble

| Item | Valeur |
|---|---|
| Provider | [Render](https://render.com) — free tier |
| Branche déployée | `develop` (chaque push redéploie automatiquement via Blueprint) |
| URL stable | `https://hotones-staging.onrender.com` |
| Plan | `free` (web service uniquement) |
| Base de données | SQLite éphémère (rechargée au boot avec fixtures démo) |
| Redis | absent (transport Messenger `sync://` — handler dispatché immédiatement, pas de worker) |
| Mailer | Mailtrap dev (US-071) — `MAILER_DSN` injecté via dashboard Render, fallback `null://null` au boot si absent |
| Coût | 0 € |
| Cold start | ~30 s après 15 min d'inactivité (limite free tier) |

## Choix d'hosting & arbitrages

Free tier choisi pour démarrer sans dépendance budget. Trade-offs assumés :

| Trade-off | Conséquence | Mitigation prévue |
|---|---|---|
| Pas de persistent disk | DB SQLite recréée à chaque boot | Fixtures rechargées par `start-staging.sh` ; OK pour démo Sprint Review |
| Sleep après 15 min idle | Cold start de ~30 s à la première requête | Acceptable en démo, réveille via simple GET avant le créneau review |
| Pas de Redis free tier + pas de worker | Transport Messenger `sync://` — emails Vacation rendus dans la requête HTTP elle-même | Acceptable pour démo (latence ajoutée minime sur l'opération approve/reject/cancel) |
| Mailer Mailtrap | Tous les emails Vacation atterrissent dans une inbox Mailtrap dev | Configurer `MAILER_DSN` côté Render dashboard, fallback `null://null` automatique si non défini |

## Comptes démo

Provisionnés au boot par `app:create-test-users`. Mot de passe : `password` pour tous.

| Email | Rôle | Persona |
|---|---|---|
| `intervenant@test.com` | ROLE_INTERVENANT | P-001 Adrien |
| `chef-projet@test.com` | ROLE_CHEF_PROJET | P-002 |
| `manager@test.com` | ROLE_MANAGER | P-003 Manon |
| `compta@test.com` | ROLE_COMPTA | P-004 |
| `admin@test.com` | ROLE_ADMIN | P-005 |
| `superadmin@test.com` | ROLE_SUPERADMIN | P-006 |

5 projets de démo générés via `app:seed-projects-2025 --count=5` (vs 50 en local) pour rester dans les limites free tier.

## Provisionnement initial (one-shot, action admin)

Procédure côté propriétaire du compte Render. Pas re-jouée par les déploiements suivants.

```bash
# 1. Connecter le repo a Render Blueprint
#    https://dashboard.render.com → Blueprints → New → from existing repo
#    Sélectionner la branche `develop` et le fichier `render.staging.yaml`
#
# 2. Render lit render.staging.yaml et crée :
#    - Service web Docker `hotones-staging` (free tier)
#
# 3. Première mise en route :
#    - Render build l'image via Dockerfile
#    - À la fin du build, exec /usr/local/bin/start-staging.sh
#    - SQLite + JWT keys + fixtures + cache warmup en série
#    - Endpoint /health répond 200 dès que Nginx + PHP-FPM tournent
```

## Cycle de déploiement automatique

```
git push origin develop
   ↓
Render webhook (auto, pas de GitHub Actions)
   ↓
Build Docker image (Dockerfile inchangé)
   ↓
Boot via /usr/local/bin/start-staging.sh
   ├── Wipe var/staging.db
   ├── doctrine:migrations:migrate
   ├── app:load-reference-data
   ├── doctrine:fixtures:load --append
   ├── app:create-test-users
   ├── app:seed-projects-2025 --count=5
   ├── assets:install public
   └── cache:warmup
   ↓
Nginx + PHP-FPM en foreground
   ↓
GET /health → 200 OK
```

## Smoke test post-déploiement

Procédure manuelle (~ 1 min) :

```bash
# 1. Réveiller le service (cold start)
curl -fsSL https://hotones-staging.onrender.com/health || exit 1

# 2. Login intervenant via le formulaire web
open https://hotones-staging.onrender.com/login
#    intervenant@test.com / password

# 3. Vérifier le cycle vacation US-066 → US-067 → US-068 → US-069
#    (cf. scenario Gherkin sprint-002 review)
```

Smoke test automatisé (Panther E2E) à venir au prochain sprint si la CI Render Webhook expose un déclencheur.

## Failure modes connus

| Symptôme | Cause | Action |
|---|---|---|
| `502 Bad Gateway` quelques secondes après push | Build Docker en cours | Attendre, le build est visible sur dashboard.render.com |
| `Database is locked` | SQLite + accès concurrent | Acceptable en démo, pas de mitigation envisagée free tier |
| Cold start > 60 s | Free tier saturée chez Render | Passer en plan `starter` ($7/mois) — voir section "Migration vers payant" |
| `Token CSRF invalide` après cold start | Sessions filesystem perdues | Re-login, c'est attendu |

## Migration vers plan payant

Quand le PO valide le budget (~30 €/mois cumulés) :

1. Éditer `render.staging.yaml` :
   - `plan: free` → `plan: starter` (web service)
   - Ajouter une `disk:` persistent pour `var/`
   - Ajouter le service Redis (cf. `render.yaml` prod)
   - Échanger SQLite contre PostgreSQL Render (`fromDatabase`)

2. Adapter `start-staging.sh` :
   - Ne plus wiper la DB au boot
   - Conserver les fixtures avec `--append` uniquement si DB vide

3. Brancher US-071 sur un Mailer transactionnel (`MAILER_DSN` Sendgrid ou Mailtrap).

## Stack PR

- Cette story : sprint-003 US-070
- Dépendance : `Dockerfile` ajoute `start-staging.sh`
- Suite logique : **US-071** (email transactionnel) qui swap `MAILER_DSN` une fois Mailtrap/Sendgrid validés

## Références

- [render.com Blueprint Spec](https://docs.render.com/blueprint-spec)
- `render.yaml` — blueprint production
- `render.staging.yaml` — blueprint staging
- `docker/scripts/start-staging.sh` — boot script
- Sprint-002 retro thème D (démo bloquée)
- Sprint-003 PRODUCT-tasks.md (T-070-01..06)
