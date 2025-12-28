# Docker Optimization Opportunities

**Date:** 2025-12-28
**Current Setup:** PHP 8.4.15-fpm-alpine, MariaDB 11.4, Redis 7, Nginx 1.27

## Current Configuration Analysis

### ✅ What's Already Optimized

1. **Multi-stage builds** - Assets built separately in Node stage
2. **Alpine Linux** - Minimal base images (~5-10MB)
3. **PHP Extensions** - Well-configured with APCu, OPcache, Redis
4. **Production optimizations**:
   - `--optimize-autoloader`
   - `--classmap-authoritative`
   - `--apcu-autoloader`
   - `--no-dev` dependencies
5. **Cached volumes** in docker-compose (`cached` mount option)
6. **Separate dev/prod** Dockerfiles

## Optimization Opportunities

### 1. Upgrade to PHP 8.5 (When Stable)

**Status:** PHP 8.5.0 RC available (not yet GA - Expected GA: January 2025)

**Benefits:**
- Property hooks (no more getters/setters boilerplate)
- Improved JIT performance
- Better error messages
- New DOM API (HTML5 parser)
- PDO driver-specific subclasses

**Migration Path:**
```bash
# Once PHP 8.5 is stable (GA), update Dockerfiles:
FROM php:8.5-fpm-alpine  # instead of 8.4

# Test compatibility:
docker compose exec app composer require --dev php:^8.5
docker compose exec app composer test
```

**Compatibility Check:**
- ✅ Symfony 8.0 supports PHP 8.5
- ✅ All current dependencies compatible (checked via packagist.org)
- ⚠️ Wait for GA release (not RC) for production

**Recommendation:** Wait 1-2 months after GA for ecosystem stability.

### 2. BuildKit Cache Mounts (Build Speed)

**Current:** Dependencies reinstalled on every build
**Improvement:** Cache Composer/npm packages between builds

```dockerfile
# syntax=docker/dockerfile:1

# In Dockerfile (production)
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --optimize-autoloader

# In Dockerfile.dev
RUN --mount=type=cache,target=/tmp/composer-cache \
    --mount=type=cache,target=/root/.npm \
    composer install && npm install
```

**Impact:**
- 50-70% faster rebuilds
- Shared cache across builds

### 3. Layer Optimization (Image Size)

**Current:** Some layers could be combined
**Improvement:** Group related commands

```dockerfile
# Before (3 layers)
RUN apk add --no-cache bash
RUN apk add --no-cache git
RUN apk add --no-cache unzip

# After (1 layer)
RUN apk add --no-cache \
    bash \
    git \
    unzip
```

**Already optimized in current setup** ✅

### 4. Health Checks in docker-compose.yml

**Current:** No health checks defined
**Improvement:** Add health checks for all services

```yaml
services:
  app:
    healthcheck:
      test: ["CMD", "php-fpm-healthcheck"]
      interval: 10s
      timeout: 3s
      retries: 3
      start_period: 40s

  db:
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 3s
      retries: 3

  web:
    healthcheck:
      test: ["CMD", "wget", "--no-verbose", "--tries=1", "--spider", "http://localhost/health"]
      interval: 10s
      timeout: 3s
      retries: 3
```

**Impact:**
- Better orchestration awareness
- Automatic unhealthy container restart
- Kubernetes-ready

### 5. Resource Limits (docker-compose.yml)

**Current:** No resource limits
**Improvement:** Define memory/CPU limits

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 1G
        reservations:
          cpus: '0.5'
          memory: 512M

  db:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
        reservations:
          cpus: '1'
          memory: 1G

  redis:
    deploy:
      resources:
        limits:
          cpus: '0.5'
          memory: 256M
        reservations:
          cpus: '0.25'
          memory: 128M
```

**Impact:**
- Prevent resource exhaustion
- Better local dev experience
- Production-like constraints

### 6. Docker Compose Networks

**Current:** Default bridge network
**Improvement:** Explicit networks with isolation

```yaml
networks:
  frontend:
    driver: bridge
  backend:
    driver: bridge
    internal: true  # No external access

services:
  web:
    networks:
      - frontend
      - backend

  app:
    networks:
      - backend

  db:
    networks:
      - backend  # Only accessible internally

  redis:
    networks:
      - backend
```

**Impact:**
- Better security isolation
- Clearer architecture
- Production-like setup

### 7. .dockerignore Optimization

**Current:** Basic .dockerignore
**Improvement:** Exclude more unnecessary files

```dockerignore
# Current + additions
.git
.github
node_modules
vendor
var/cache/*
var/log/*
var/sessions/*
public/assets/*
.env.*.local
.phpunit.result.cache
*.log

# Development tools
.idea/
.vscode/
.php-cs-fixer.cache
phpstan.neon
infection.json5
deptrac.yaml

# Documentation (not needed in production)
docs/
*.md
!README.md

# Tests
tests/
phpunit.xml.dist
```

**Impact:**
- Smaller build context
- Faster builds
- Smaller final images

### 8. OPcache Configuration Tuning

**Current:** Basic OPcache config
**Improvement:** Optimize for production workload

```ini
; docker/php/php-prod.ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256  ; Up from 128
opcache.interned_strings_buffer=32  ; Up from 16
opcache.max_accelerated_files=20000  ; Up from 10000
opcache.revalidate_freq=0  ; Never check (prod)
opcache.validate_timestamps=0  ; Disable file checks (prod)
opcache.preload=/var/www/html/config/preload.php
opcache.preload_user=www-data
opcache.jit=tracing
opcache.jit_buffer_size=128M  ; Enable JIT
```

**Impact:**
- 20-40% performance boost
- Reduced I/O
- Better memory usage

### 9. PHP-FPM Pool Configuration

**Current:** Default PHP-FPM settings
**Improvement:** Tune for production workload

```ini
; docker/php/php-fpm-prod.conf
[www]
pm = dynamic
pm.max_children = 50  ; Adjust based on memory
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500  ; Restart workers after N requests
pm.status_path = /fpm-status
ping.path = /fpm-ping
```

**Impact:**
- Better concurrency handling
- Memory leak prevention
- Health check endpoints

### 10. Database Configuration

**Current:** Default MariaDB settings
**Improvement:** Optimize for workload

```yaml
# docker-compose.yml
db:
  image: mariadb:11.4
  command:
    - --innodb-buffer-pool-size=1G
    - --innodb-log-file-size=256M
    - --max-connections=200
    - --query-cache-type=0
    - --character-set-server=utf8mb4
    - --collation-server=utf8mb4_unicode_ci
```

**Impact:**
- Better query performance
- More connections
- UTF8MB4 by default

### 11. Redis Persistence Configuration

**Current:** `appendonly yes` (good)
**Improvement:** Optimize AOF settings

```yaml
redis:
  command:
    - redis-server
    - --appendonly yes
    - --appendfsync everysec  # Balance safety/performance
    - --maxmemory 256mb
    - --maxmemory-policy allkeys-lru
    - --save ""  # Disable RDB snapshots (AOF only)
```

**Impact:**
- Predictable memory usage
- Automatic eviction policy
- Faster restarts

## Implementation Priority

### High Priority (Do Now)
1. ✅ **Health checks** - Essential for production readiness
2. ✅ **Resource limits** - Prevent local dev issues
3. ✅ **OPcache tuning** - Immediate performance gain

### Medium Priority (Next Sprint)
4. **BuildKit cache mounts** - Developer experience
5. **PHP-FPM pool tuning** - Production performance
6. **Database optimization** - Query performance

### Low Priority (Future)
7. **Network isolation** - Security hardening
8. **PHP 8.5 upgrade** - Wait for GA + 1-2 months
9. **.dockerignore cleanup** - Marginal gains

## PHP 8.5 Migration Checklist

### Before Upgrade
- [ ] Wait for PHP 8.5.0 GA release (expected Jan 2025)
- [ ] Wait 4-6 weeks for ecosystem stabilization
- [ ] Review PHP 8.5 migration guide
- [ ] Check all Composer dependencies compatibility
- [ ] Review deprecations in current code

### During Upgrade
- [ ] Update Dockerfile: `FROM php:8.5-fpm-alpine`
- [ ] Update Dockerfile.dev: `FROM php:8.5-fpm-alpine`
- [ ] Rebuild containers: `docker compose build --no-cache`
- [ ] Run full test suite
- [ ] Run static analysis (PHPStan)
- [ ] Profile performance differences

### After Upgrade
- [ ] Monitor production logs for warnings
- [ ] Benchmark performance improvements
- [ ] Update CI/CD pipelines
- [ ] Document breaking changes (if any)

## Monitoring Recommendations

### Metrics to Track
- Container CPU/Memory usage (Docker stats)
- PHP-FPM pool statistics (pm.status_path)
- OPcache hit rate (opcache_get_status)
- Database slow query log
- Redis memory usage

### Tools
- **Docker stats:** `docker stats`
- **cAdvisor:** Container monitoring
- **Grafana + Prometheus:** Metrics visualization
- **Blackfire:** PHP profiling (already documented)

## References

- PHP 8.5 Release Notes: https://www.php.net/releases/8.5/en.php
- Docker Best Practices: https://docs.docker.com/develop/dev-best-practices/
- PHP-FPM Tuning: https://www.php.net/manual/en/install.fpm.configuration.php
- OPcache Configuration: https://www.php.net/manual/en/opcache.configuration.php

---

**Version:** 1.0.0
**Last Updated:** 2025-12-28
