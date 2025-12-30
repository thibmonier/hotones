# Logging Guide

This guide explains how to use structured logging in the HotOnes application.

## Overview

The application uses **Monolog** with JSON-formatted structured logging for better observability and log analysis.

## Log Channels

The application provides specialized log channels for different types of events:

| Channel | Purpose | Examples |
|---------|---------|----------|
| `app` | Default application logs | General application events |
| `business` | Business logic events | Orders created, profitability calculated, time tracked |
| `security` | Security-related events | Login attempts, 2FA validation, authorization failures |
| `performance` | Performance monitoring | Slow queries, cache misses, high memory usage |
| `deprecation` | Deprecation warnings | Symfony/PHP deprecations |

## Log Files

### Development Environment

- **Main log**: `var/log/dev.log` (all logs, JSON format)
- **Errors log**: `var/log/errors.log` (errors only, JSON format)
- **Business events**: `var/log/business.log` (business channel only, JSON format)
- **Console**: Logs are also displayed in the console (human-readable format)

### Production Environment

All logs are sent to `php://stderr` (Docker container standard output) in JSON format:

- **Main log**: Errors and critical issues (with buffering)
- **Business events**: All business-related events (info and above)
- **Security events**: All security events (info and above)
- **Performance**: Performance issues (warning and above)

## Structured Log Format

All logs include the following contextual information:

```json
{
  "message": "User logged in successfully",
  "context": {},
  "level": 200,
  "level_name": "INFO",
  "channel": "security",
  "datetime": "2025-12-28T12:30:45.123456+00:00",
  "extra": {
    "request_id": "20251228123045-a1b2c3d4",
    "request_method": "POST",
    "request_uri": "/login",
    "client_ip": "192.168.1.100",
    "environment": "production",
    "session_id": "abc12345",
    "user_email": "john.doe@example.com",
    "user_id": 42,
    "memory_usage_mb": 12.5,
    "memory_peak_mb": 15.2,
    "execution_time_ms": 125.5
  }
}
```

### Contextual Fields

- **request_id**: Unique ID for correlating all logs from the same HTTP request
- **request_method**: HTTP method (GET, POST, etc.)
- **request_uri**: Request URI/path
- **client_ip**: Client IP address
- **environment**: Application environment (dev, prod, test)
- **session_id**: Session ID (first 8 characters for privacy)
- **user_email**: Authenticated user's email (if logged in)
- **user_id**: Authenticated user's ID (if logged in)
- **memory_usage_mb**: Current memory usage in MB
- **memory_peak_mb**: Peak memory usage in MB
- **execution_time_ms**: Execution time since request start in milliseconds

## Usage Examples

### Basic Logging

```php
use Psr\Log\LoggerInterface;

class MyService
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function doSomething(): void
    {
        $this->logger->info('Operation started');

        try {
            // Do something...
            $this->logger->info('Operation completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Operation failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
```

### Business Events Logging

```php
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OrderService
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.business')]
        private LoggerInterface $logger
    ) {
    }

    public function createOrder(Order $order): void
    {
        // Create order...

        $this->logger->info('Order created', [
            'order_id' => $order->getId(),
            'total_amount' => $order->getTotalAmount(),
            'client_name' => $order->getClient()->getName(),
            'items_count' => count($order->getOrderLines()),
        ]);
    }
}
```

### Security Events Logging

```php
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class LoginService
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.security')]
        private LoggerInterface $logger
    ) {
    }

    public function onLoginSuccess(string $email): void
    {
        $this->logger->info('User logged in successfully', [
            'email' => $email,
            'auth_method' => '2FA',
        ]);
    }

    public function onLoginFailure(string $email, string $reason): void
    {
        $this->logger->warning('Login attempt failed', [
            'email' => $email,
            'reason' => $reason,
        ]);
    }
}
```

### Performance Monitoring

```php
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AnalyticsService
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.performance')]
        private LoggerInterface $logger
    ) {
    }

    public function calculateKPIs(): void
    {
        $startTime = microtime(true);

        // Heavy calculation...

        $executionTime = microtime(true) - $startTime;

        if ($executionTime > 1.0) { // More than 1 second
            $this->logger->warning('Slow KPI calculation detected', [
                'execution_time_seconds' => $executionTime,
                'memory_mb' => memory_get_peak_usage(true) / 1024 / 1024,
            ]);
        }
    }
}
```

## Log Levels

Use appropriate log levels for different situations:

| Level | When to Use | Examples |
|-------|-------------|----------|
| `DEBUG` | Detailed debugging info | Variable values, step-by-step execution |
| `INFO` | Informational messages | Successful operations, state changes |
| `NOTICE` | Normal but significant | Configuration loaded, service started |
| `WARNING` | Warning conditions | Deprecated usage, non-critical errors |
| `ERROR` | Error conditions | Exceptions, failed operations |
| `CRITICAL` | Critical conditions | System component unavailable |
| `ALERT` | Action required immediately | Data corruption detected |
| `EMERGENCY` | System unusable | Database connection lost |

## Searching and Analyzing Logs

### Local Development

```bash
# View all logs
docker compose logs -f app

# View only errors
docker compose exec app tail -f var/log/errors.log

# Search for specific request
docker compose exec app grep "20251228123045-a1b2c3d4" var/log/dev.log

# Parse JSON logs with jq
docker compose exec app cat var/log/dev.log | jq '
  select(.level_name == "ERROR") |
  {time: .datetime, message: .message, user: .extra.user_email}
'

# Find slow requests
docker compose exec app cat var/log/dev.log | jq '
  select(.extra.execution_time_ms > 1000) |
  {uri: .extra.request_uri, time_ms: .extra.execution_time_ms}
'
```

### Production (Log Aggregation)

For production environments, consider integrating with:

- **ELK Stack** (Elasticsearch, Logstash, Kibana)
- **Graylog**
- **Datadog**
- **Sentry** (for error tracking)
- **CloudWatch** (AWS)

Example Filebeat configuration for ELK:

```yaml
filebeat.inputs:
  - type: container
    paths:
      - '/var/lib/docker/containers/*/*.log'
    json.keys_under_root: true
    json.add_error_key: true

processors:
  - add_docker_metadata: ~

output.elasticsearch:
  hosts: ["elasticsearch:9200"]
  index: "hotones-logs-%{+yyyy.MM.dd}"
```

## Best Practices

### DO:
- ✅ Use appropriate log channels (`business`, `security`, `performance`)
- ✅ Include contextual data in the `context` array
- ✅ Use structured data (arrays/objects) instead of string concatenation
- ✅ Log business-critical events (orders, payments, user actions)
- ✅ Log security events (login attempts, authorization failures)
- ✅ Log performance issues (slow queries, high memory usage)
- ✅ Use appropriate log levels
- ✅ Include error context (exception message, stack trace)

### DON'T:
- ❌ Log sensitive data (passwords, credit cards, API keys)
- ❌ Log PII without anonymization (full names, addresses)
- ❌ Use string concatenation for log messages
- ❌ Log in tight loops (causes performance issues)
- ❌ Mix different concerns in one log message
- ❌ Log binary data or very large payloads

### Examples

**Good:**
```php
$this->logger->info('Order created', [
    'order_id' => $order->getId(),
    'total_amount' => $order->getTotalAmount(),
    'payment_method' => $order->getPaymentMethod(), // Generic info
]);
```

**Bad:**
```php
$this->logger->info('Order created with ID '.$order->getId().' for amount '.$order->getTotalAmount().' paid with card ending in '.$order->getCardLastFour());
// ❌ String concatenation, includes PII (card number)
```

## Monitoring and Alerting

### Key Metrics to Monitor

1. **Error Rate**: Percentage of requests resulting in errors
2. **Response Time**: P50, P95, P99 percentiles
3. **Memory Usage**: Average and peak memory consumption
4. **Log Volume**: Number of logs per minute/hour
5. **Failed Logins**: Number of authentication failures

### Alert Examples

```yaml
# Example alert rules (Prometheus/AlertManager format)
groups:
  - name: application_alerts
    rules:
      - alert: HighErrorRate
        expr: rate(app_errors_total[5m]) > 0.05
        annotations:
          summary: "High error rate detected"

      - alert: SlowResponse
        expr: histogram_quantile(0.95, app_request_duration_ms) > 2000
        annotations:
          summary: "95th percentile response time > 2s"
```

## Troubleshooting

### No Logs Appearing

**Check log file permissions:**
```bash
docker compose exec app ls -la var/log/
```

**Verify Monolog configuration:**
```bash
docker compose exec app php bin/console debug:config monolog
```

### JSON Parsing Errors

**Validate JSON format:**
```bash
docker compose exec app tail -n 1 var/log/dev.log | jq .
```

### Missing Context Information

**Clear cache to reload processors:**
```bash
docker compose exec app php bin/console cache:clear
```

## Related Documentation

- [Health Check Endpoints](./health-checks.md) - Application health monitoring
- [Security Audit](./security-audit.md) - Security guidelines and best practices

---

**Version:** 1.0.0
**Last Updated:** 2025-12-28
