# Blackfire Profiling Guide

This guide explains how to set up Blackfire profiling for the HotOnes application and identify performance bottlenecks in critical user journeys.

## Overview

**Blackfire** is a PHP profiling tool that helps identify performance bottlenecks, memory leaks, and optimization opportunities. It provides detailed call graphs, performance metrics, and actionable recommendations.

## Installation

### 1. Get Blackfire Credentials

Sign up for a Blackfire account at https://blackfire.io and obtain your credentials:
- Server ID
- Server Token
- Client ID
- Client Token

### 2. Update Docker Configuration

Add Blackfire services to `docker-compose.yml`:

```yaml
services:
  # ... existing services ...

  blackfire:
    image: blackfire/blackfire:2
    environment:
      BLACKFIRE_SERVER_ID: ${BLACKFIRE_SERVER_ID}
      BLACKFIRE_SERVER_TOKEN: ${BLACKFIRE_SERVER_TOKEN}
      BLACKFIRE_CLIENT_ID: ${BLACKFIRE_CLIENT_ID}
      BLACKFIRE_CLIENT_TOKEN: ${BLACKFIRE_CLIENT_TOKEN}
    networks:
      - hotones-network

  app:
    # ... existing app config ...
    environment:
      # ... existing env vars ...
      BLACKFIRE_SERVER_ID: ${BLACKFIRE_SERVER_ID}
      BLACKFIRE_SERVER_TOKEN: ${BLACKFIRE_SERVER_TOKEN}
    depends_on:
      - blackfire
```

### 3. Install Blackfire PHP Extension

Update the `Dockerfile` to install the Blackfire extension:

```dockerfile
# Install Blackfire probe
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && architecture=$(uname -m) \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/$architecture/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8307\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz
```

### 4. Add Credentials to .env

Add your Blackfire credentials to `.env.local`:

```env
BLACKFIRE_SERVER_ID=your-server-id
BLACKFIRE_SERVER_TOKEN=your-server-token
BLACKFIRE_CLIENT_ID=your-client-id
BLACKFIRE_CLIENT_TOKEN=your-client-token
```

**Important:** Add `.env.local` to `.gitignore` to avoid committing credentials.

### 5. Rebuild Docker Containers

```bash
docker compose down
docker compose build --no-cache app
docker compose up -d
```

### 6. Verify Installation

```bash
docker compose exec app php -m | grep blackfire
# Should output: blackfire

docker compose exec app blackfire version
# Should output Blackfire version information
```

## Critical User Journeys to Profile

The following user journeys are performance-critical and should be profiled regularly:

### 1. Analytics Dashboard Loading
**Why Critical:** Heavy KPI calculations, multiple aggregations
**Endpoint:** `GET /analytics/dashboard`
**Scenario:**
- Load dashboard with default filters (current year, monthly granularity)
- Load dashboard with custom filters (specific year, quarterly granularity)
- Load dashboard with project filters (specific project type)

**Expected Performance:**
- Initial load: < 2 seconds
- Filtered load: < 1.5 seconds

**Profile Command:**
```bash
blackfire curl http://localhost:8080/analytics/dashboard
blackfire curl "http://localhost:8080/analytics/dashboard?year=2024&granularity=monthly"
```

### 2. Staffing Dashboard (Annual View)
**Why Critical:** Complex weekly calculations for all contributors
**Endpoint:** `GET /staffing/dashboard/annual`
**Scenario:**
- Load annual view with weekly granularity
- Load with profile filter
- Load with contributor filter

**Expected Performance:**
- Annual view: < 3 seconds
- Filtered view: < 2 seconds

**Profile Command:**
```bash
blackfire curl http://localhost:8080/staffing/dashboard/annual?year=2025
blackfire curl "http://localhost:8080/staffing/dashboard/annual?year=2025&profile=1"
```

### 3. Sales Dashboard with Filters
**Why Critical:** Multiple complex aggregations and statistics
**Endpoint:** `GET /sales-dashboard`
**Scenario:**
- Load dashboard with year filter
- Load with user filter
- Load with role filter

**Expected Performance:**
- Dashboard load: < 1.5 seconds
- Filtered load: < 1 second

**Profile Command:**
```bash
blackfire curl http://localhost:8080/sales-dashboard?year=2025
blackfire curl "http://localhost:8080/sales-dashboard?year=2025&user_id=1"
```

### 4. Project Profitability Calculation
**Why Critical:** Real-time margin calculations across all tasks
**Endpoint:** `GET /profitability/dashboard`
**Scenario:**
- Load profitability dashboard
- Filter by project type
- Filter by date range

**Expected Performance:**
- Dashboard load: < 2 seconds
- Filtered load: < 1.5 seconds

**Profile Command:**
```bash
blackfire curl http://localhost:8080/profitability/dashboard
blackfire curl "http://localhost:8080/profitability/dashboard?project_type=forfait"
```

### 5. Timesheet Weekly View
**Why Critical:** Frequently accessed by all users
**Endpoint:** `GET /timesheets`
**Scenario:**
- Load current week
- Load week with many entries
- Bulk entry submission

**Expected Performance:**
- Week load: < 500ms
- Submission: < 1 second

**Profile Command:**
```bash
blackfire curl http://localhost:8080/timesheets
```

### 6. Order Creation (Complex Quote)
**Why Critical:** Multiple sections, many line items, task generation
**Endpoint:** `POST /orders/new`
**Scenario:**
- Create order with 5+ sections
- Create order with 20+ line items
- Create order with task auto-generation

**Expected Performance:**
- Order creation: < 1.5 seconds

**Profile Command:**
```bash
# Requires authentication and CSRF token - use browser extension
# Or create a profiling script with proper session handling
```

### 7. Planning Resource Timeline
**Why Critical:** Renders complex calendar view with many contributors
**Endpoint:** `GET /planning`
**Scenario:**
- Load planning view
- Drag-drop planning update
- Weekly staffing rate calculation

**Expected Performance:**
- Initial load: < 2 seconds
- Update: < 500ms

**Profile Command:**
```bash
blackfire curl http://localhost:8080/planning
```

### 8. Excel Export (Large Dataset)
**Why Critical:** Generates large XLSX files with calculations
**Endpoint:** `GET /analytics/export-excel`
**Scenario:**
- Export full year analytics
- Export with all project types
- Export with multiple worksheets

**Expected Performance:**
- Export generation: < 5 seconds

**Profile Command:**
```bash
blackfire curl "http://localhost:8080/analytics/export-excel?year=2024"
```

## Profiling Workflow

### 1. Profile Critical Paths

```bash
# Profile analytics dashboard
blackfire curl http://localhost:8080/analytics/dashboard

# Profile with custom scenario name
blackfire curl --title="Analytics Dashboard 2024" \
  "http://localhost:8080/analytics/dashboard?year=2024"

# Profile with metadata for comparison
blackfire curl --title="Staffing Annual View" \
  --metadata="env=production" \
  --metadata="year=2025" \
  http://localhost:8080/staffing/dashboard/annual
```

### 2. Analyze Results

Access the profile in Blackfire UI:
- Open the provided URL
- Review the call graph
- Identify bottlenecks (red/orange nodes)
- Check recommendations tab
- Review SQL queries
- Examine memory usage

### 3. Compare Profiles

```bash
# Create baseline profile
blackfire curl --title="Before Optimization" http://localhost:8080/analytics/dashboard

# After optimization, create comparison profile
blackfire curl --title="After Optimization" http://localhost:8080/analytics/dashboard
```

In Blackfire UI:
- Go to the second profile
- Click "Compare"
- Select the baseline profile
- Analyze performance improvements

## Common Performance Issues and Solutions

### Issue 1: N+1 Query Problem

**Symptom:** Many identical SELECT queries in the profile
**Detection:** Look for repeated queries in SQL tab
**Solution:** Use eager loading with QueryBuilder joins

```php
// Before (N+1)
foreach ($projects as $project) {
    $client = $project->getClient(); // Triggers query
}

// After (Eager loading)
$qb->select('p', 'c')
    ->from(Project::class, 'p')
    ->leftJoin('p.client', 'c')
    ->addSelect('c');
```

### Issue 2: Slow Repository Query

**Symptom:** Repository method takes significant time
**Detection:** Red/orange node in call graph for repository method
**Solution:** Add database indexes, optimize query

```sql
-- Add index on frequently filtered columns
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_timesheets_date ON timesheets(date);
CREATE INDEX idx_projects_type ON projects(type);
```

### Issue 3: Heavy Twig Template Rendering

**Symptom:** Twig rendering takes significant time
**Detection:** Large time spent in `Twig_Template::render()`
**Solution:** Cache computed values, reduce template complexity

```php
// Before (computed in template)
{% set total = 0 %}
{% for item in items %}
    {% set total = total + item.price %}
{% endfor %}

// After (computed in controller)
$total = array_sum(array_map(fn($item) => $item->getPrice(), $items));
```

### Issue 4: Unoptimized Serialization

**Symptom:** JSON serialization takes significant time
**Detection:** Time spent in `json_encode()` or normalizers
**Solution:** Use custom normalizers, exclude unnecessary data

```php
// Use groups to control serialization
use Symfony\Component\Serializer\Annotation\Groups;

#[Groups(['api:list'])]
private int $id;

#[Groups(['api:detail'])] // Only in detail view
private string $description;
```

### Issue 5: Cache Misses

**Symptom:** Expensive calculations executed every request
**Detection:** Same expensive method called on every profile
**Solution:** Implement caching with appropriate TTL

```php
// Add caching to expensive operations
$metrics = $this->cache->get('dashboard_metrics_'.$year, function (ItemInterface $item) use ($year) {
    $item->expiresAfter(1800); // 30 minutes
    return $this->calculateMetrics($year);
});
```

## Performance Targets

### Response Time Targets (P95)

| Endpoint Type | Target | Critical Threshold |
|---------------|--------|-------------------|
| Dashboard views | < 2s | 3s |
| List pages | < 500ms | 1s |
| Form submissions | < 1s | 2s |
| API endpoints | < 200ms | 500ms |
| Excel exports | < 5s | 10s |
| Health checks | < 100ms | 200ms |

### Memory Usage Targets

| Endpoint Type | Target | Critical Threshold |
|---------------|--------|-------------------|
| Dashboard views | < 50MB | 100MB |
| List pages | < 30MB | 50MB |
| Exports | < 128MB | 256MB |
| API endpoints | < 20MB | 40MB |

### Database Query Targets

| Metric | Target | Critical Threshold |
|--------|--------|-------------------|
| Queries per request | < 20 | 50 |
| Query time (average) | < 10ms | 50ms |
| Query time (P95) | < 50ms | 200ms |

## Automated Profiling

### Continuous Profiling

Set up automated profiling in CI/CD:

```yaml
# .github/workflows/performance.yml
name: Performance Testing

on:
  pull_request:
    branches: [ main ]

jobs:
  profile:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Blackfire
        uses: blackfireio/github-action@v1
        with:
          server-id: ${{ secrets.BLACKFIRE_SERVER_ID }}
          server-token: ${{ secrets.BLACKFIRE_SERVER_TOKEN }}
          client-id: ${{ secrets.BLACKFIRE_CLIENT_ID }}
          client-token: ${{ secrets.BLACKFIRE_CLIENT_TOKEN }}

      - name: Profile Critical Paths
        run: |
          blackfire curl http://localhost:8080/analytics/dashboard
          blackfire curl http://localhost:8080/staffing/dashboard/annual
          blackfire curl http://localhost:8080/sales-dashboard
```

### Performance Assertions

Create Blackfire test scenarios:

```yaml
# .blackfire.yaml
tests:
  "Analytics Dashboard Performance":
    path: "/analytics/dashboard"
    assertions:
      - "main.wall_time < 2s"
      - "main.memory < 50mb"
      - "metrics.sql.queries.count < 30"

  "Staffing Annual View Performance":
    path: "/staffing/dashboard/annual"
    assertions:
      - "main.wall_time < 3s"
      - "main.memory < 75mb"
      - "metrics.sql.queries.count < 50"
```

## Best Practices

### DO:
- ✅ Profile before and after optimization
- ✅ Use realistic data volumes (not empty database)
- ✅ Profile in production-like environment
- ✅ Set up automated profiling for critical paths
- ✅ Create performance budgets and assertions
- ✅ Compare profiles to detect regressions
- ✅ Profile both happy path and edge cases

### DON'T:
- ❌ Profile in debug mode (distorts results)
- ❌ Profile with Xdebug enabled
- ❌ Optimize without profiling first
- ❌ Profile only on local machine
- ❌ Ignore memory usage (focus only on time)
- ❌ Profile once and assume it's representative

## Troubleshooting

### Blackfire Not Connecting

**Check agent status:**
```bash
docker compose ps blackfire
docker compose logs blackfire
```

**Verify PHP extension:**
```bash
docker compose exec app php -i | grep blackfire
```

### Profiles Not Appearing

**Check credentials:**
```bash
docker compose exec app env | grep BLACKFIRE
```

**Test agent connection:**
```bash
docker compose exec app blackfire client:verify-server-credentials
```

### Performance Issues in Production

**Enable production profiling:**
```yaml
# config/packages/prod/blackfire.yaml
blackfire:
    enabled: true
```

**Profile specific requests:**
- Install Blackfire browser extension
- Visit the slow page
- Click "Profile" in browser extension

## Related Documentation

- [Caching Guide](./caching.md) - Cache implementation strategies
- [Database Optimization](./database-optimization.md) - Query optimization
- [Performance Monitoring](./logging-guide.md) - Performance logging

## Resources

- Blackfire Documentation: https://docs.blackfire.io/
- Blackfire Blog: https://blog.blackfire.io/
- Symfony Performance Guide: https://symfony.com/doc/current/performance.html

---

**Version:** 1.0.0
**Last Updated:** 2025-12-28
