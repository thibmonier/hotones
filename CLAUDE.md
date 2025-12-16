# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

HotOnes is a project management and profitability tracking system for a web agency. It handles the complete lifecycle from client quotes to time tracking to profitability analysis, with sophisticated analytics and KPI dashboards.

**Stack:** Symfony 7.4, PHP 8.4, MariaDB 11.4, Twig + Bootstrap 5, Webpack Encore

**Key Features:** Multi-role authentication (2FA), contributor/employment management, project tracking (forfait/régie), quote generation, timesheet entry, profitability analytics, staffing/planning dashboard

## Essential Commands

### Docker Environment
```bash
# Start/stop
docker compose up -d --build
docker compose down

# Access PHP container
docker compose exec app bash

# View logs
docker compose logs -f app
docker compose logs -f web
```

### Symfony Console
```bash
# Database
docker compose exec app php bin/console doctrine:migrations:migrate
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:schema:validate

# User management
docker compose exec app php bin/console app:user:create email password "First" "Last"

# Test data generation
docker compose exec app php bin/console app:generate-test-data --year=2024

# Analytics/metrics calculation
docker compose exec app php bin/console app:calculate-metrics [year] [--granularity=monthly|quarterly|yearly]
docker compose exec app php bin/console app:calculate-staffing-metrics [year] [--granularity=weekly|monthly|quarterly]
docker compose exec app php bin/console app:metrics:dispatch --year=2025

# Scheduler
docker compose exec app php bin/console debug:scheduler              # List all scheduled tasks
docker compose exec app php bin/console messenger:consume scheduler_default  # Run the scheduler worker

# Message queue
docker compose exec app php bin/console messenger:consume async -vv
docker compose exec app php bin/console messenger:failed:retry
```

### Code Quality
```bash
# Run all quality checks
docker compose exec app composer check-code

# Individual checks
docker compose exec app composer phpstan              # Static analysis (level 3 + strict rules)
docker compose exec app composer phpcsfixer           # Code style check (dry-run)
docker compose exec app composer phpcsfixer-fix       # Fix code style
docker compose exec app composer phpcs                # PHP_CodeSniffer (PSR-12 + quality rules)
docker compose exec app composer phpcbf               # PHP Code Beautifier and Fixer (auto-fix)
```

### Testing
```bash
# All tests
docker compose exec app composer test

# By suite
docker compose exec app composer test-unit            # Unit tests
docker compose exec app composer test-functional      # Functional tests
docker compose exec app composer test-integration     # Integration tests
docker compose exec app composer test-api             # API tests
docker compose exec app composer test-e2e             # End-to-end (Panther)

# Single test file
docker compose exec app ./vendor/bin/phpunit tests/Unit/Path/To/TestFile.php
```

### Assets
```bash
# Local build
./build-assets.sh dev         # Development
./build-assets.sh watch       # Watch mode
./build-assets.sh prod        # Production

# Docker build
./docker-build-assets.sh dev
./docker-build-assets.sh watch
```

## Architecture

### Code Organization
- **Controllers** (`src/Controller/`): Thin controllers, attribute-based routing, security via `#[IsGranted()]`
- **Entities** (`src/Entity/`): Doctrine entities with PHP attributes, lifecycle callbacks
- **Repositories** (`src/Repository/`): Custom query methods, extends `ServiceEntityRepository`
- **Services** (`src/Service/`): Business logic layer
- **Forms** (`src/Form/`): Form type classes
- **Commands** (`src/Command/`): CLI commands for admin/maintenance tasks
- **Templates** (`templates/`): Twig templates with Bootstrap 5 (Skote theme)

### Security & Authentication
- **Role Hierarchy:** ROLE_INTERVENANT → ROLE_CHEF_PROJET → ROLE_MANAGER → ROLE_COMPTA/ROLE_ADMIN → ROLE_SUPERADMIN
- **2FA:** TOTP via scheb/2fa-bundle (enable at `/me/2fa/enable`)
- **API:** JWT authentication via lexik/jwt-authentication-bundle
- **CSRF:** All forms and state-changing actions protected

### Database
- **ORM:** Doctrine 3.5+ with lazy ghost objects, PHP attributes
- **Migrations:** 35+ versioned migrations in `/migrations/`
- **Connection:** `mysql://symfony:symfony@db:3306/hotones` (MariaDB 11.4)
- **Test DB:** SQLite in-memory (configured in `.env.test`)
- **External access:** localhost:3307

## Key Entity Relationships

### Core Business Model
```
User (1:1 optional) ↔ Contributor
  ├─► EmploymentPeriod (1:Many) - with Profile (Many:Many)
  ├─► Timesheet (1:Many)
  └─► ProjectTask assignments

Project (Many:1) ↔ Client
  ├─► Order (1:Many) - quotes/proposals
  │   └─► OrderSection → OrderLine (generates ProjectTask when signed)
  ├─► ProjectTask (1:Many)
  │   └─► ProjectSubTask (1:Many) - kanban subtasks
  ├─► Timesheet (1:Many)
  └─► Technology (Many:Many), ServiceCategory (Many:1)

Timesheet
  ├─► Contributor (Many:1)
  ├─► Project (Many:1)
  ├─► ProjectTask (Many:1 optional)
  └─► ProjectSubTask (Many:1 optional)
```

### Analytics Model (Star Schema)
```
FactProjectMetrics (aggregated KPIs)
  ├─► DimTime (temporal dimension)
  ├─► DimProjectType, DimContributor
  └─► Metrics: revenue, costs, margin, utilization

FactStaffingMetrics
  ├─► DimTime, DimProfile, DimContributor
  └─► Metrics: availability, workload, TACE
```

**Services**:
- `DashboardReadService`: Reads pre-calculated KPIs from star schema (FactProjectMetrics) with automatic fallback to real-time calculation if data is missing
- `MetricsCalculationService`: Deprecated real-time service, kept as fallback only
- `ExcelExportService`: Exports dashboard data to Excel with multiple worksheets (KPIs, monthly evolution, distributions)

**Automation**:
- Symfony Scheduler configured via `AnalyticsScheduleProvider`
- Daily recalculation at 6:00 AM (monthly metrics)
- Quarterly recalculation on 1st day at 7:00 AM (Q1,Q2,Q3,Q4)
- Annual recalculation on January 1st at 8:00 AM
- Metrics dispatched via Messenger to `RecalculateMetricsMessage`

## Important Patterns

### Entity Patterns
- Lifecycle callbacks: `#[ORM\PrePersist]`, `#[ORM\PreUpdate]` for timestamps
- Soft deletes where needed
- Indexed columns for performance (status, dates, foreign keys)
- bcmath for decimal calculations (money, percentages)

### Controller Patterns
- Thin controllers, delegate logic to services/repositories
- Flash messages for user feedback
- CSRF protection on forms: `$this->isCsrfTokenValid('token_id', $token)`
- Route naming: `{entity}_{action}` (e.g., `project_show`, `order_edit`)

### Repository Patterns
- Custom query methods with QueryBuilder
- Named parameters (`:param`) for security
- Filter methods accepting arrays for flexible queries
- Joins with `addSelect()` to avoid N+1 queries

### Naming Conventions
- **Entities:** Singular PascalCase (`Project`, `OrderLine`)
- **Tables:** snake_case plural (`projects`, `order_lines`)
- **Controllers:** `{Entity}Controller`
- **Repositories:** `{Entity}Repository`
- **Routes:** snake_case (`project_show`, `order_edit`)

## Business Logic Notes

### Profitability Calculation
- Only tasks with `countsForProfitability=true` AND `type=regular` are counted
- Use `estimatedHoursRevised` OR fallback to `estimatedHoursSold`
- Revenue: `daysEstimated * tjm` (from OrderLine)
- Cost: `hoursSpent * contributor_cjm`
- Margin: `(revenue - cost) / revenue * 100`

### Default Tasks
- Every project auto-creates "AVV" and "Non-vendu" tasks
- These are excluded from profitability by default (`countsForProfitability=false`)

### Order → Task Generation
- OrderLines generate ProjectTasks when order status = signed/won
- One ProjectTask per OrderLine with budget data copied over

### Time Tracking
- Weekly timesheet entry interface
- Timer feature: start/stop from timesheet page (one active at a time)
- Minimum imputation: 0.125 days (1 hour)
- Aggregation: SubTask → Task → Project (bottom-up)

### Planning
- Resource Timeline view using FullCalendar Scheduler
- One line per contributor (grouped by user)
- Drag-drop to move/resize plannings
- Vacation display in disabled colors
- Weekly staffing rate calculated and displayed
- Read-only for completed weeks

## Development Workflow

### After Pull
```bash
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate -n
./build-assets.sh dev
```

### Creating a Migration
```bash
# Modify entity classes, then:
docker compose exec app php bin/console make:migration
# Review the migration file, then:
docker compose exec app php bin/console doctrine:migrations:migrate
```

### Adding a New Feature
1. Create/modify entities in `src/Entity/`
2. Generate migration: `docker compose exec app php bin/console make:migration`
3. Create repository methods in `src/Repository/`
4. Implement service logic in `src/Service/` (if complex)
5. Create controller with routes in `src/Controller/`
6. Create forms in `src/Form/` (if needed)
7. Create templates in `templates/`
8. Write tests in `tests/`
9. Run quality checks: `docker compose exec app composer check-code`
10. Run tests: `docker compose exec app composer test`

### Code Style Rules
- PSR-12 + Symfony standards
- PHPStan level 3 with strict rules extension
- PHP_CodeSniffer with PSR-12 + quality rules
- Strict types declaration: `declare(strict_types=1);`
- No Yoda conditions
- Aligned operators, ordered imports
- PHP CS Fixer configuration in `.php-cs-fixer.dist.php`
- PHP_CodeSniffer configuration in `phpcs.xml`

**Quality Tools:**
- **PHP CS Fixer**: PSR-12 + Symfony coding standards, auto-formatting
- **PHPStan**: Static analysis with Doctrine/Symfony extensions + strict rules
- **PHP_CodeSniffer**: PSR-12 compliance, cyclomatic complexity, code quality metrics

## Testing

### Test Environment
- `.env.test` uses SQLite for isolation
- DAMA doctrine-test-bundle for transaction rollback between tests
- Foundry for fixtures and factories
- Panther for E2E browser tests

### Test Structure
- **Unit:** `tests/Unit/` - Pure logic, no dependencies
- **Integration:** `tests/Integration/` - Database interactions
- **Functional:** `tests/Functional/` - HTTP requests
- **API:** `tests/Api/` - API endpoints
- **E2E:** `tests/E2E/` - Browser automation (Panther)

### Running Specific Tests
```bash
# Single test file
docker compose exec app ./vendor/bin/phpunit tests/Unit/Service/MyServiceTest.php

# Single test method
docker compose exec app ./vendor/bin/phpunit --filter testMethodName

# With coverage
docker compose exec app ./vendor/bin/phpunit --coverage-html coverage/
```

## Documentation

**Primary Index:** `WARP.md` - Links to all documentation

**Key Documentation Files:**
- `docs/architecture.md` - Technical stack and bundles
- `docs/entities.md` - Entity relationships and data model
- `docs/features.md` - Feature descriptions and business logic
- `docs/profitability.md` - Profitability calculation formulas
- `docs/analytics.md` - KPIs, metrics system, star schema
- `docs/time-planning.md` - Time tracking and planning features
- `docs/tests.md` - Testing strategy and execution
- `docs/good-practices.md` - Code quality and performance guidelines
- `AGENTS.md` - Guidelines for AI agents

**Documentation Philosophy:** WARP.md as index, detailed docs in `/docs/`, no duplication, machine-readable structure

## Common Pitfalls

### Date Calculations
- Always use `\DateTime` or `\DateTimeImmutable`
- FullCalendar uses exclusive end dates (subtract 1 day when converting)
- Doctrine date fields are timezone-aware

### Decimal Precision
- Use `bcmath` functions for money/percentage calculations
- Doctrine decimal fields are strings in PHP (cast explicitly)
- Example: `$total = bcadd($price1, $price2, 2);`

### Query Performance
- Always use `addSelect()` on joins to avoid N+1 queries
- Index foreign keys and frequently filtered columns
- Use query result caching for expensive analytics queries

### Security
- Never bypass CSRF on state-changing operations
- Always validate user input in forms
- Use parameterized queries (Doctrine handles this)
- Check authorization with voters for complex permissions

## Useful URLs

- Application: http://localhost:8080
- API Documentation: http://localhost:8080/api/documentation
- Admin config: `/admin/technologies`, `/admin/service-categories`, `/admin/job-profiles`, `/admin/company-settings`
- Analytics Dashboard: `/analytics/dashboard` (with Excel export at `/analytics/export-excel`)
- Profitability Dashboard: `/profitability/dashboard`
- Sales Dashboard: `/sales/dashboard`
- Staffing Dashboard: `/staffing/dashboard`
- Planning: `/planning`
- Employment Periods: `/employment-periods`
- 2FA Setup: `/me/2fa/enable`
