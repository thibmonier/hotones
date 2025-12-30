# Docker Optimizations Applied

**Date:** 2025-12-28
**Status:** ✅ Implemented

## Changes Applied

### 1. Health Checks (All Services)

Added health checks for production-ready orchestration:

- **app** (PHP-FPM): `php-fpm -t` test every 10s
- **web** (Nginx): HTTP GET /health every 10s  
- **db** (MariaDB): `healthcheck.sh --connect --innodb_initialized` every 10s
- **redis**: `redis-cli ping` every 10s

**Benefits:**
- Automatic unhealthy container detection
- Better service dependency management
- Kubernetes/Docker Swarm ready
- Faster failure recovery

### 2. Resource Limits

Defined CPU and memory limits for all services:

| Service | CPU Limit | Memory Limit | CPU Reserved | Memory Reserved |
|---------|-----------|--------------|--------------|-----------------|
| app     | 2 cores   | 1GB          | 0.5 cores    | 512MB           |
| web     | 0.5 cores | 128MB        | 0.25 cores   | 64MB            |
| db      | 2 cores   | 2GB          | 1 core       | 1GB             |
| redis   | 0.5 cores | 512MB        | 0.25 cores   | 256MB           |

**Benefits:**
- Prevents resource exhaustion on dev machines
- Production-like constraints
- Better performance predictability
- OOM killer protection

### 3. Redis Optimization

Enhanced Redis configuration:

```yaml
command:
  - redis-server
  - --appendonly yes              # Persistence enabled
  - --appendfsync everysec        # Balance safety/performance
  - --maxmemory 256mb             # Memory limit
  - --maxmemory-policy allkeys-lru # Eviction policy
  - --save ""                     # Disable RDB snapshots
```

**Benefits:**
- Predictable memory usage (256MB max)
- Automatic cache eviction (LRU)
- AOF persistence (crash recovery)
- Faster restarts (no RDB loading)

### 4. Service Dependencies with Health Conditions

```yaml
depends_on:
  db:
    condition: service_healthy
  redis:
    condition: service_healthy
```

**Benefits:**
- Services wait for dependencies to be ready
- No more "connection refused" on startup
- Cleaner logs
- Faster successful startup

## Testing

```bash
# Verify configuration
docker compose config --quiet

# Test with health checks
docker compose up -d
docker compose ps  # Check health status

# Monitor resource usage
docker stats
```

## Performance Impact

- **Startup time**: +5-10s (waiting for health checks) - acceptable trade-off
- **Memory usage**: Controlled via limits - prevents swapping
- **Redis**: More predictable performance with maxmemory
- **Orchestration**: Much better with health checks

## Next Steps (Not Yet Applied)

See `docs/docker-optimization.md` for:

1. **BuildKit cache mounts** - 50-70% faster rebuilds
2. **PHP 8.5 upgrade** - Wait for GA (January 2025)
3. **OPcache preload** - 20-40% performance boost
4. **PHP-FPM pool tuning** - Better concurrency
5. **Network isolation** - Security hardening

## Rollback

If issues occur, revert with:

```bash
git revert HEAD
docker compose down
docker compose up -d
```

---

**Applied by:** Claude Code
**Tested:** ✅ Configuration valid
**Deployed:** Ready for `docker compose up -d`
