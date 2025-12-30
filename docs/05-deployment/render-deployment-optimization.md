# Optimisation du Déploiement Render

## 📊 Situation Actuelle vs. Optimisée

| Phase | Avant | Après | Gain |
|-------|-------|-------|------|
| **Docker Build** | 10-20 min | 4-8 min | **-6 à -12 min** |
| **Container Startup** | 4-10 min | 2-4 min | **-2 à -6 min** |
| **Total déploiement** | **14-30 min** | **6-12 min** | **-8 à -18 min** |

### 🎯 Gain estimé : **50-60% de réduction** du temps de déploiement

---

## 🔴 Optimisations Critiques Implémentées

### 1. Cache BuildKit pour Composer (Économie : 3-5 min)

**Problème :** Les dépendances Composer (78 packages, ~19K lignes dans composer.lock) sont réinstallées à chaque build, même si `composer.lock` n'a pas changé.

**Solution :** Utilisation de BuildKit cache mount + layer séparé pour vendor/

```dockerfile
# Stage dédié aux dépendances Composer
FROM php:8.4-fpm-alpine AS composer-deps

# Cache mount pour Composer (persiste entre builds)
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts

# Copy vendor dans stage final (layer caché si composer.lock identique)
COPY --from=composer-deps /var/www/html/vendor/ vendor/
```

**Impact :**
- Premier build : ~5 minutes (normal)
- Builds suivants (code changé, lock inchangé) : ~30 secondes
- **Économie : 3-5 min par déploiement**

### 2. Cache BuildKit pour Yarn (Économie : 1-2 min)

**Problème :** Les modules Node.js (23 packages) sont réinstallés à chaque build.

**Solution :** Cache mount pour Yarn

```dockerfile
RUN --mount=type=cache,target=/root/.yarn \
    --mount=type=cache,target=/app/node_modules \
    yarn install --frozen-lockfile
```

**Impact :**
- Premier build : ~4 minutes (normal)
- Builds suivants : ~1 minute
- **Économie : 1-2 min par déploiement**

### 3. Suppression du Cache Warmup Dupliqué (Économie : 1-2 min)

**Problème :** Cache warmup exécuté 2 fois :
- Dans le Dockerfile (ligne 104-105) → 1-2 min
- Au startup du conteneur (ligne 150-154) → 2-5 min

**Solution :** Supprimer du Dockerfile, garder uniquement au startup

```dockerfile
# AVANT (Dockerfile ligne 104-105)
RUN APP_ENV=prod php bin/console cache:clear --no-warmup || true \
    && APP_ENV=prod php bin/console cache:warmup || true

# APRÈS : Supprimé du Dockerfile
# Cache warmup uniquement dans start-render-optimized.sh avec DATABASE_URL disponible
```

**Impact :**
- Build Docker plus rapide : -1-2 min
- Warmup au startup reste nécessaire (besoin de DATABASE_URL)
- **Économie : 1-2 min au build**

### 4. Retry Logic Optimisé avec Exponential Backoff (Économie : 1-2 min)

**Problème :** Attente database/Redis avec retry fixe de 2s (30 tentatives = 60s max)

```bash
# AVANT (start-render.sh ligne 42-49)
until php bin/console dbal:run-sql "SELECT 1" || [ $attempt -eq 30 ]; do
    sleep 2  # Toujours 2 secondes
done
```

**Solution :** Exponential backoff + moins de tentatives

```bash
# APRÈS (start-render-optimized.sh)
wait_for_database() {
    local max_attempts=15  # Réduit de 30 à 15
    local wait_time=1

    while [ $attempt -lt $max_attempts ]; do
        # Tentative de connexion
        sleep $wait_time
        # Exponential backoff: 1s, 2s, 4s, 8s (max)
        wait_time=$((wait_time < 8 ? wait_time * 2 : 8))
    done
}
```

**Impact :**
- Connexion réussie au 1er essai : ~1s (vs 2s avant)
- Connexion réussie au 3e essai : ~7s (vs 6s avant, mais plus rapide en moyenne)
- Timeout complet (échec) : ~60s (vs 60s avant, mais détecté plus vite)
- **Économie moyenne : 30-60s par déploiement**

### 5. Checks Parallèles DB + Redis (Économie : 10-15s)

**Problème :** Vérifications séquentielles (database wait → redis wait)

```bash
# AVANT : Séquentiel
wait_for_database()  # 15-30s
wait_for_redis()     # 5-15s
# Total : 20-45s
```

**Solution :** Exécution parallèle avec background jobs

```bash
# APRÈS : Parallèle
wait_for_database &
db_pid=$!
wait_for_redis &
redis_pid=$!

wait $db_pid
wait $redis_pid
# Total : max(db_time, redis_time) ≈ 15-30s
```

**Impact :**
- Avant : 20-45s (somme)
- Après : 15-30s (max des deux)
- **Économie : 10-15s par déploiement**

---

## 🟡 Optimisations Secondaires

### 6. Migration Check Avant Exécution (Économie : 10-30s)

**Problème :** Migrations exécutées à chaque startup, même si déjà à jour

**Solution :** Check préalable avec `doctrine:migrations:up-to-date`

```bash
migration_status=$(php bin/console doctrine:migrations:up-to-date 2>&1)

if echo "$migration_status" | grep -q "up-to-date"; then
    echo "✓ Database up-to-date, skipping migrations"
else
    php bin/console doctrine:migrations:migrate
fi
```

**Impact :**
- 90% des déploiements : skip migrations (économie de 10-30s)
- 10% des déploiements : exécute migrations (temps normal)
- **Économie moyenne : 10-20s par déploiement**

### 7. Base Images Pinnées (Reproductibilité)

**Problème :** Tags flottants (`node:22-alpine`, `php:8.4-fpm-alpine`) peuvent changer

**Solution :** Pinning par SHA256

```dockerfile
FROM node:22-alpine@sha256:6e80991f69cc7722c561e5d14d5e72ab47c0d6b6cfb3ae50fb9cf9a7b30fdf97
FROM php:8.4-fpm-alpine@sha256:...
```

**Impact :**
- Builds reproductibles (même image à chaque fois)
- Pas d'économie de temps directe, mais améliore la fiabilité

### 8. Healthcheck Intégré (Améliore Rollout)

**Ajout :** Healthcheck dans Dockerfile

```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "exit(file_get_contents('http://localhost:8080/health') === 'OK' ? 0 : 1);"
```

**Impact :**
- Render détecte plus vite si le déploiement est sain
- Réduit les rollouts échoués (meilleure fiabilité)

---

## 📋 Récapitulatif des Gains

| Optimisation | Économie Temps | Fréquence Impact | Priorité |
|--------------|----------------|------------------|----------|
| Cache BuildKit Composer | 3-5 min | 80% déploiements | 🔴 Critique |
| Cache BuildKit Yarn | 1-2 min | 60% déploiements | 🔴 Critique |
| Suppression warmup Docker | 1-2 min | 100% déploiements | 🔴 Critique |
| Exponential backoff | 30-60s | 100% déploiements | 🟡 Haute |
| Checks parallèles | 10-15s | 100% déploiements | 🟡 Haute |
| Migration check | 10-30s | 90% déploiements | 🟠 Moyenne |

**Total économisé par déploiement :** **8-18 minutes** (selon contexte)

---

## 🚀 Migration vers la Version Optimisée

### Étape 1 : Activer BuildKit sur Render

Modifier `render.yaml` pour utiliser le Dockerfile optimisé :

```yaml
services:
  - type: web
    name: hotones
    env: docker
    dockerfilePath: ./Dockerfile.optimized  # ← Nouveau
    dockerContext: .

    # Activer BuildKit cache (nécessite plan Render avec BuildKit)
    buildCommand: |
      docker buildx create --use
      docker buildx build \
        --cache-from=type=registry,ref=$RENDER_GIT_REPO_SLUG:buildcache \
        --cache-to=type=registry,ref=$RENDER_GIT_REPO_SLUG:buildcache,mode=max \
        -t $RENDER_GIT_REPO_SLUG:latest \
        --load \
        .
```

**Note :** BuildKit cache nécessite Render **Standard plan ou supérieur** (pas disponible sur Free tier).

### Étape 2 : Tester en Local

```bash
# Build avec cache BuildKit
export DOCKER_BUILDKIT=1
export BUILDKIT_PROGRESS=plain

docker buildx create --use --name hotones-builder
docker buildx build \
  --cache-from=type=local,src=/tmp/buildx-cache \
  --cache-to=type=local,dest=/tmp/buildx-cache,mode=max \
  -f Dockerfile.optimized \
  -t hotones:optimized \
  --load \
  .

# Test du conteneur
docker run --rm -p 8080:8080 \
  -e DATABASE_URL="mysql://user:pass@host:3306/db" \
  -e REDIS_URL="redis://host:6379" \
  -e APP_ENV=prod \
  hotones:optimized
```

### Étape 3 : Déploiement Progressif

1. **Déploiement test :** Utiliser Dockerfile.optimized sur un environnement de staging
2. **Validation :** Vérifier que le déploiement fonctionne (<12 min au lieu de 20-30 min)
3. **Migration production :** Renommer Dockerfile → Dockerfile.legacy et Dockerfile.optimized → Dockerfile

```bash
# Backup actuel
mv Dockerfile Dockerfile.legacy

# Activer version optimisée
mv Dockerfile.optimized Dockerfile

# Commit et push
git add Dockerfile docker/scripts/start-render-optimized.sh
git commit -m "opt: Optimize Render deployment with BuildKit cache (50-60% faster)"
git push origin main
```

---

## 📊 Métriques à Surveiller Après Migration

### Build Time (Render Dashboard)

- **Avant :** 10-20 min
- **Cible :** 4-8 min
- **Indicateur de succès :** Réduction de >40%

### Startup Time (Logs)

```bash
# Rechercher dans les logs Render
grep "Application ready!" logs.txt
```

- **Avant :** 4-10 min après build
- **Cible :** 2-4 min après build
- **Indicateur de succès :** Réduction de >50%

### Cache Hit Rate (BuildKit logs)

```
[+] Building 92.5s (18/18) FINISHED
 => CACHED [composer-deps 3/5] RUN composer install   0.1s
 => CACHED [assets 2/4] RUN yarn install             0.1s
```

- **Cible :** >80% des layers "CACHED" sur déploiements code-only
- **Indicateur :** Présence de "CACHED" dans les logs buildx

---

## ⚠️ Points d'Attention

### 1. BuildKit Cache Invalide si composer.lock/yarn.lock Change

**Comportement :** Lorsque `composer.lock` ou `yarn.lock` est modifié, le cache est invalidé et les dépendances sont réinstallées.

**Impact :** Premier build après mise à jour de dépendances : temps normal (~15-20 min)

**Mitigation :** C'est normal et attendu. Les builds suivants bénéficieront du cache.

### 2. Render Free Tier ne Supporte pas BuildKit Cache Registry

**Limitation :** `--cache-from=type=registry` nécessite un plan payant Render.

**Alternative pour Free Tier :**

```yaml
# Utiliser cache local (moins efficace mais gratuit)
buildCommand: |
  docker build \
    --cache-from=hotones:latest \
    -t hotones:latest \
    -f Dockerfile.optimized \
    .
```

**Impact :** Gain réduit à ~30-40% au lieu de 50-60%.

### 3. DATABASE_URL Obligatoire au Startup (Cache Warmup)

**Problème :** Le cache warmup nécessite DATABASE_URL pour charger les métadonnées Doctrine.

**Solution :** Assurer que DATABASE_URL est défini dans Render Environment Variables AVANT le premier déploiement.

**Vérification :**

```bash
# Dans le conteneur Render
echo $DATABASE_URL
# Doit afficher : mysql://...
```

### 4. Permissions Filesystem (var/)

**Problème potentiel :** Cache warmup écrit dans `var/cache/`, nécessite permissions www-data.

**Solution :** Le script optimisé définit les permissions en parallèle (ligne 155-158).

**Vérification :**

```bash
ls -la /var/www/html/var/cache/
# Doit afficher : drwxrwxr-x www-data www-data
```

---

## 🧪 Tests de Validation

### Test 1 : Vérifier le Cache BuildKit

```bash
# Premier build (full)
time docker buildx build -f Dockerfile.optimized -t test:1 .
# Doit prendre ~10-15 min

# Modifier un fichier PHP (pas composer.lock)
echo "// test" >> src/Controller/DefaultController.php

# Second build (avec cache)
time docker buildx build -f Dockerfile.optimized -t test:2 .
# Doit prendre ~3-5 min (réduction de >50%)
```

**Résultat attendu :** Ligne "CACHED" pour layers Composer et Yarn.

### Test 2 : Vérifier le Startup Optimisé

```bash
# Lancer conteneur avec timer
time docker run --rm \
  -e DATABASE_URL="mysql://..." \
  -e REDIS_URL="redis://..." \
  test:2

# Observer les logs pour timing
# Rechercher :
# - "✓ Database ready (attempt 1)" → <5s
# - "✓ Cache warmed up in Xs" → <60s
```

**Résultat attendu :** Startup complet en <3 minutes.

### Test 3 : Vérifier les Migrations Skip

```bash
# Deuxième démarrage du conteneur (DB déjà migrée)
docker logs -f <container_id> | grep migration

# Doit afficher :
# "✓ Database schema up-to-date, skipping migrations"
```

**Résultat attendu :** Pas d'exécution de migrations au 2e startup.

---

## 🔮 Optimisations Futures (Gains Additionnels Possibles)

### 1. Preload Opcache (Gain : 10-20% performance runtime)

Activer le preloading Opcache dans `php-prod.ini` :

```ini
opcache.preload=/var/www/html/config/preload.php
opcache.preload_user=www-data
```

**Impact :** Pas de réduction de temps de déploiement, mais améliore performance runtime (+10-20% sur requêtes HTTP).

### 2. Multi-Stage Cache pour Webpack (Gain : 30-60s)

Utiliser webpack cache persistence :

```javascript
// webpack.config.js
module.exports = {
  cache: {
    type: 'filesystem',
    buildDependencies: {
      config: [__filename]
    }
  }
}
```

**Impact :** Webpack rebuilds plus rapides (30-60s économisés).

### 3. Lazy Messenger Workers Startup (Gain : 5-10s)

Démarrer workers Messenger après HTTP ready (via supervisor eventlistener).

**Impact :** Startup HTTP plus rapide, workers démarrent en arrière-plan.

### 4. Distroless Base Image (Gain : 1-2 min build)

Utiliser image distroless au lieu de Alpine :

```dockerfile
FROM gcr.io/distroless/php8-fpm
```

**Impact :**
- Image finale plus petite (~40% réduction)
- Pull/push plus rapide (~1-2 min économisés)
- Meilleure sécurité (moins de packages)

**Trade-off :** Debugging plus difficile (pas de shell bash).

---

## 📚 Références

### Documentation Officielle

- [BuildKit cache mounts](https://docs.docker.com/build/cache/backends/)
- [Render Docker deployments](https://render.com/docs/docker)
- [Symfony cache warmup](https://symfony.com/doc/current/performance.html#cache-warmup)
- [Doctrine migrations](https://www.doctrine-project.org/projects/doctrine-migrations/en/3.6/reference/introduction.html)

### Fichiers Modifiés

- `/Dockerfile.optimized` - Nouveau Dockerfile avec BuildKit cache
- `/docker/scripts/start-render-optimized.sh` - Script startup optimisé
- `/docs/render-deployment-optimization.md` - Cette documentation

### Commandes Utiles

```bash
# Analyser les layers Docker (voir taille et cache)
docker history hotones:latest

# Mesurer temps de build avec détails
time DOCKER_BUILDKIT=1 docker build --progress=plain -f Dockerfile.optimized .

# Vérifier cache hit rate
docker buildx du --verbose

# Nettoyer cache BuildKit (si problèmes)
docker buildx prune -af
```

---

## 🎯 Checklist de Déploiement

- [ ] Backup du Dockerfile actuel (`mv Dockerfile Dockerfile.legacy`)
- [ ] Tester Dockerfile.optimized en local (build + run)
- [ ] Vérifier que DATABASE_URL et REDIS_URL sont configurés dans Render
- [ ] Déployer sur environnement de staging
- [ ] Mesurer temps de build/startup (doit être <12 min total)
- [ ] Vérifier logs pour "CACHED" dans buildx output
- [ ] Valider que l'application fonctionne correctement
- [ ] Migrer en production (renommer Dockerfile.optimized → Dockerfile)
- [ ] Surveiller métriques pendant 7 jours
- [ ] Documenter résultats réels vs. estimations

---

## ✅ Résumé Exécutif

### Problème
Déploiements Render actuels : **14-30 minutes** (build Docker 10-20 min + startup 4-10 min)

### Solution
5 optimisations majeures :
1. Cache BuildKit pour Composer/Yarn
2. Suppression cache warmup dupliqué
3. Exponential backoff + checks parallèles
4. Smart migration detection
5. Startup script optimisé

### Résultat Attendu
- **Temps de déploiement : 6-12 minutes** (réduction de 50-60%)
- **Build Docker : 4-8 minutes** (vs 10-20 min)
- **Startup : 2-4 minutes** (vs 4-10 min)

### Effort de Migration
- **Complexité :** Faible (2 nouveaux fichiers)
- **Risque :** Faible (backward compatible, Dockerfile.legacy disponible)
- **Temps d'implémentation :** ~1 heure (tests inclus)

### ROI
- **Économie par déploiement :** 10-20 minutes
- **Fréquence déploiements :** ~5-10 par semaine
- **Économie mensuelle :** **5-15 heures d'attente** économisées
