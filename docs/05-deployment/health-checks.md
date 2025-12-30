# Health Check Endpoints

This document describes the health check endpoints available for monitoring and orchestration.

## Overview

The application provides three health check endpoints designed for different monitoring scenarios:

1. **Full Health Check** (`/health`) - Comprehensive health status with detailed checks
2. **Liveness Probe** (`/health/live`) - Lightweight check to verify the application is running
3. **Readiness Probe** (`/health/ready`) - Check if the application is ready to serve traffic

All endpoints are **publicly accessible** (no authentication required) and return JSON responses.

## Endpoints

### 1. Full Health Check

**Endpoint:** `GET /health`
**Purpose:** Comprehensive health monitoring with detailed status for all critical components
**Use Case:** Monitoring dashboards, alerting systems, detailed diagnostics

**Response (200 OK - Healthy):**
```json
{
  "status": "healthy",
  "timestamp": "2025-12-28T12:13:23+00:00",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful"
    },
    "cache": {
      "status": "healthy",
      "message": "Cache system operational"
    },
    "filesystem": {
      "status": "healthy",
      "message": "Filesystem is writable"
    }
  },
  "metadata": {
    "version": "1.0.0",
    "symfony_version": "8.0.2",
    "php_version": "8.4.15",
    "environment": "dev"
  }
}
```

**Response (503 Service Unavailable - Unhealthy):**
```json
{
  "status": "unhealthy",
  "timestamp": "2025-12-28T12:13:23+00:00",
  "checks": {
    "database": {
      "status": "unhealthy",
      "message": "Database connection failed: SQLSTATE[HY000] [2002] Connection refused"
    },
    "cache": {
      "status": "healthy",
      "message": "Cache system operational"
    },
    "filesystem": {
      "status": "healthy",
      "message": "Filesystem is writable"
    }
  },
  "metadata": {
    "version": "1.0.0",
    "symfony_version": "8.0.2",
    "php_version": "8.4.15",
    "environment": "production"
  }
}
```

**Checks Performed:**
- **Database**: Tests connection with `SELECT 1` query
- **Cache**: Writes, reads, and deletes a test item to/from Redis
- **Filesystem**: Creates, reads, and deletes a test file in `var/cache/`

**HTTP Status Codes:**
- `200 OK` - All checks passed (status: "healthy")
- `503 Service Unavailable` - One or more checks failed (status: "unhealthy")

---

### 2. Liveness Probe

**Endpoint:** `GET /health/live`
**Purpose:** Verify the application process is running and responding
**Use Case:** Kubernetes liveness probe, container orchestration

**Response (200 OK):**
```json
{
  "status": "alive",
  "timestamp": "2025-12-28T12:13:39+00:00"
}
```

**Characteristics:**
- **Lightweight**: No deep checks, minimal resource usage
- **Fast**: Returns immediately
- **Always succeeds**: Returns 200 if the application can respond

**Use Case:**
- Kubernetes/Docker liveness probe to restart crashed containers
- Load balancer basic health check

---

### 3. Readiness Probe

**Endpoint:** `GET /health/ready`
**Purpose:** Verify the application is ready to accept traffic
**Use Case:** Kubernetes readiness probe, load balancer routing

**Response (200 OK - Ready):**
```json
{
  "status": "ready",
  "timestamp": "2025-12-28T12:13:39+00:00",
  "checks": {
    "database": "ready",
    "cache": "ready"
  }
}
```

**Response (503 Service Unavailable - Not Ready):**
```json
{
  "status": "not ready",
  "timestamp": "2025-12-28T12:13:39+00:00",
  "checks": {
    "database": "not ready",
    "cache": "ready"
  }
}
```

**Checks Performed:**
- **Database**: Tests connection
- **Cache**: Tests read/write operations

**HTTP Status Codes:**
- `200 OK` - All critical dependencies are ready
- `503 Service Unavailable` - One or more critical dependencies are not ready

**Use Case:**
- Kubernetes readiness probe to control traffic routing
- Load balancer health checks for traffic distribution
- Deployment orchestration (wait for application to be ready)

---

## Integration Examples

### Kubernetes Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: hotones-app
spec:
  template:
    spec:
      containers:
      - name: app
        image: hotones:latest
        ports:
        - containerPort: 8080
        livenessProbe:
          httpGet:
            path: /health/live
            port: 8080
          initialDelaySeconds: 30
          periodSeconds: 10
          timeoutSeconds: 5
          failureThreshold: 3
        readinessProbe:
          httpGet:
            path: /health/ready
            port: 8080
          initialDelaySeconds: 10
          periodSeconds: 5
          timeoutSeconds: 3
          successThreshold: 1
          failureThreshold: 3
```

### Docker Compose Healthcheck

```yaml
services:
  app:
    image: hotones:latest
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/health/ready"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
```

### Monitoring with curl

```bash
# Full health check
curl -s http://localhost:8080/health | jq .

# Check only status
curl -s http://localhost:8080/health | jq -r '.status'

# Check specific component
curl -s http://localhost:8080/health | jq -r '.checks.database.status'

# Liveness probe
curl -s http://localhost:8080/health/live

# Readiness probe
curl -s http://localhost:8080/health/ready
```

### Prometheus Monitoring (Example)

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'hotones-health'
    metrics_path: '/health'
    scrape_interval: 30s
    static_configs:
      - targets: ['localhost:8080']
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
```

---

## Best Practices

### When to Use Each Endpoint

| Endpoint | Use Case | Frequency | Action on Failure |
|----------|----------|-----------|-------------------|
| `/health` | Monitoring dashboards, alerting | 1-5 minutes | Alert ops team |
| `/health/live` | Container orchestration | 10-30 seconds | Restart container |
| `/health/ready` | Load balancer routing | 5-10 seconds | Remove from pool |

### Monitoring Strategy

1. **Alerting**: Monitor `/health` endpoint every 1-5 minutes
   - Alert if status = "unhealthy"
   - Alert if any check fails
   - Alert on 503 responses

2. **Load Balancing**: Use `/health/ready` for traffic routing
   - Check every 5-10 seconds
   - Remove instance from pool on failure
   - Re-add when healthy

3. **Container Orchestration**: Use `/health/live` for liveness
   - Check every 10-30 seconds
   - Restart container after 3 consecutive failures
   - Allow 30-60 seconds initial delay

### Response Time Targets

- `/health/live`: < 100ms (no deep checks)
- `/health/ready`: < 500ms (database + cache check)
- `/health`: < 1000ms (comprehensive checks)

### Security Considerations

- Health check endpoints are **publicly accessible** by design
- They do not expose sensitive information
- Version information is included but is not considered sensitive
- No PII or credentials are returned

---

## Troubleshooting

### Common Issues

**Issue**: Database check fails
**Cause**: Database connection refused, credentials invalid, network issue
**Solution**: Check database service status, verify credentials, check network connectivity

**Issue**: Cache check fails
**Cause**: Redis connection refused, network issue, Redis full
**Solution**: Check Redis service status, verify REDIS_URL configuration, check Redis memory

**Issue**: Filesystem check fails
**Cause**: Permissions issue, disk full
**Solution**: Check `var/cache/` permissions, verify disk space

### Debugging

```bash
# Test database connection manually
docker compose exec app php bin/console dbal:run-sql "SELECT 1"

# Test Redis connection manually
docker compose exec app redis-cli -u $REDIS_URL ping

# Check filesystem permissions
docker compose exec app ls -la var/cache/

# Check disk space
docker compose exec app df -h
```

---

## Implementation Details

### Controller
- **Class**: `App\Controller\HealthCheckController`
- **Location**: `src/Controller/HealthCheckController.php`
- **Dependencies**: `Doctrine\DBAL\Connection`, `CacheItemPoolInterface`

### Security Configuration
- **Access**: Public (no authentication required)
- **Configuration**: `config/packages/security.yaml` (access_control)

### Tests
- **Location**: `tests/Functional/Controller/HealthCheckControllerTest.php`
- **Coverage**: 9 test methods, 60 assertions
- **Run**: `./vendor/bin/phpunit tests/Functional/Controller/HealthCheckControllerTest.php`

---

## Version History

- **1.0.0** (2025-12-28): Initial implementation with full health check, liveness, and readiness probes
