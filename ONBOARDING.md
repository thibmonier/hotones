# HotOnes - Developer Onboarding Guide

**Analysis Date:** January 6, 2026

## Table of Contents

- [Overview](#overview)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Quick Start (5 minutes)](#quick-start-5-minutes)
  - [Common Gotchas](#common-gotchas)
- [Architecture](#architecture)
  - [Directory Structure](#directory-structure)
  - [Key Abstractions & Patterns](#key-abstractions--patterns)
- [How To](#how-to)
  - [Daily Development Workflow](#daily-development-workflow)
  - [Adding New Features](#adding-new-features)
  - [Running Tests](#running-tests)
  - [Working with Database Migrations](#working-with-database-migrations)
  - [Asset Development](#asset-development)
- [Key Insights](#key-insights)
  - [Code Quality Standards](#code-quality-standards)
  - [Security & Authorization](#security--authorization)
  - [Naming Conventions](#naming-conventions)
  - [Recent Major Features](#recent-major-features)
  - [Common Commands Beyond Basics](#common-commands-beyond-basics)
  - [Important URLs](#important-urls)
  - [Testing Philosophy](#testing-philosophy)
- [Dependencies & Integrations](#dependencies--integrations)
- [Open Questions & Uncertainties](#open-questions--uncertainties)
- [Additional Resources](#additional-resources)

---

## Overview

**HotOnes** is a project profitability management system for web agencies. It provides comprehensive financial analysis by cross-referencing sales data (days/daily rates), time tracking, costs (daily cost rates), purchases, and consolidated KPIs.

**Core Purpose:** Help agencies understand and optimize project profitability by tracking the full lifecycle from quotes to execution to financial outcomes.

**Tech Stack:**
- **Backend:** Symfony 8.0, PHP 8.5
- **Database:** MariaDB 11.4 (Docker)
- **Frontend:** Twig templates + Bootstrap 5 (Skote theme)
- **Assets:** Webpack Encore + Sass
- **API:** API Platform 4 (REST)
- **Authentication:** 2FA via scheb/2fa-bundle (TOTP)
- **AI Integration:** OpenAI GPT-4o-mini and Anthropic Claude 3.5 Haiku for planning optimization

**License:** Creative Commons BY-NC-SA 4.0 (non-commercial use only)

---

## Getting Started

### Prerequisites
- Docker + Docker Compose (required)
- Node.js + Yarn (for local asset building)

### Quick Start (5 minutes)

```bash
# 1. Launch the environment
docker compose up -d --build

# 2. Install PHP dependencies
docker compose exec app composer install

# 3. Run database migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n

# 4. Build assets (choose one method)
# Option A: Local build
./build-assets.sh dev

# Option B: Docker build
./docker-build-assets.sh dev

# 5. Create an admin user
docker compose exec app php bin/console app:user:create email@example.com password "FirstName" "LastName"

# 6. (Optional) Generate test data
docker compose exec app php bin/console app:generate-test-data --year=$(date +%Y)
```

**Access the application:** http://localhost:8080

### Database Access (External Clients)
- Host: `localhost`
- Port: `3307`
- Database: `hotones`
- User/Pass: `symfony`/`symfony`

### Common Gotchas

1. **Port Conflicts:** If port 8080 or 3307 is already in use, modify `docker-compose.yml`
2. **Asset Building:** The `watch` mode is essential for frontend development - without it, CSS/JS changes won't rebuild automatically
3. **2FA Setup:** Users must manually configure 2FA at `/me/2fa/enable` after first login
4. **Test Environment:** Uses SQLite (not MariaDB) for isolation - see `.env.test`

---

## Architecture

### Directory Structure

```
hotones/
├── src/                    # Application code (PSR-4: App\)
│   ├── AI/                # AI integration services (OpenAI, Anthropic)
│   ├── Command/           # CLI commands (39 commands)
│   ├── Controller/        # HTTP controllers (66 controllers)
│   ├── Entity/            # Doctrine ORM entities (61 entities)
│   ├── Repository/        # Database repositories (51 repos)
│   ├── Service/           # Business logic services (36 services)
│   ├── Form/              # Symfony forms
│   ├── Event/             # Domain events
│   ├── EventSubscriber/   # Event listeners
│   ├── Message/           # Async messages (Messenger)
│   ├── MessageHandler/    # Message handlers
│   ├── Factory/           # Object factories (Foundry)
│   ├── Security/          # Security/auth logic
│   └── Twig/              # Twig extensions
├── config/                # Application configuration
│   ├── packages/          # Bundle configurations
│   ├── routes/            # Route definitions
│   └── services.yaml      # Service container config
├── templates/             # Twig templates (88 templates)
├── assets/                # Frontend assets (JS, CSS, Sass)
├── public/                # Web root
│   ├── index.php         # Front controller
│   └── build/            # Compiled assets (generated)
├── migrations/            # Database migrations (79 migrations)
├── tests/                 # Test suite
├── var/                   # Cache, logs, generated files
└── vendor/                # Composer dependencies
```

### Key Abstractions & Patterns

#### Multi-Tenancy: Company-Based Isolation
This is **the most important architectural concept** in HotOnes. The entire system is designed as a multi-tenant SaaS platform:

- **`Company`** entity is the root tenant - every piece of data belongs to a Company
- **`CompanyOwnedInterface`** marks entities that are tenant-scoped (Projects, Orders, Users, Timesheets, etc.)
- **Automatic tenant filtering** in `CompanyAwareRepository` prevents cross-tenant data leakage
- **Security enforcement** via `CompanyVoter` ensures users can only access their company's data

**Why this matters:** When adding new features, any entity that stores customer data MUST implement `CompanyOwnedInterface` and have a company relationship. This is critical for data isolation and security.

#### Domain Model Structure

The application models a web agency's complete project lifecycle:

```
Company (Tenant)
  ├── Users & Contributors (employees/freelancers)
  ├── Clients (customers)
  │   └── Projects (work being done)
  │       ├── Orders/Quotes (devis - sections with line items)
  │       ├── ProjectTasks (AVV, Non-vendu, etc.)
  │       └── Timesheets (weekly time tracking)
  ├── EmploymentPeriods (contributor cost & availability)
  ├── Vacations (time off management)
  └── Analytics & Forecasts (profitability analysis)
```

**Key Domain Concepts:**
- **Project Types:** `forfait` (fixed-price) vs `régie` (time & materials)
- **Project Status:** Active, internal/client, various lifecycle states
- **Orders/Devis:** Quotes with sections containing line items (days/daily rates/purchases)
- **Timesheets:** Weekly time entry, can optionally link to ProjectTasks
- **TACE (Taux d'Affectation sur Charge Externe):** Staffing utilization metric
- **CJM (Coût Journalier Moyen):** Daily cost rate per contributor
- **TJM (Taux Journalier Moyen):** Daily billing rate

#### Architectural Layers (Enforced by Deptrac)

The codebase follows a clean architecture with enforced dependencies:

```
Presentation Layer
  └── Controller/          # HTTP endpoints (66 controllers)
      ↓ can use
Application Layer
  ├── Service/             # Business logic (36 services)
  ├── Command/             # CLI commands (39 commands)
  ├── Form/                # Symfony forms
  ├── EventListener/       # Event listeners
  ├── MessageHandler/      # Async message handlers
  └── Message/             # Async messages
      ↓ can use
Domain Layer
  ├── Entity/              # Domain entities (61 entities)
  ├── Repository/          # Data access (51 repositories)
  └── Enum/                # Enumerations
      ↑ can be used by all
Infrastructure Layer
  ├── Security/            # Auth & authorization
  └── Scheduler/           # Scheduled tasks
```

**Golden Rule:** Controllers MUST be thin - delegate to Services for business logic. This is validated by Deptrac.

#### Property Promotion & PHP 8.4 Features

The codebase heavily uses modern PHP 8.4 syntax:

```php
// Property hooks (new in PHP 8.4)
public string $name {
    get => $this->name;
    set {
        $this->name = $value;
    }
}

// Constructor property promotion
public function __construct(
    private readonly EntityManagerInterface $em,
    private readonly ProjectRepository $repository,
) {}
```

**Convention:** All code uses `declare(strict_types=1);` and type hints everywhere

---

## How To

### Daily Development Workflow

```bash
# Start services
docker compose up -d

# Watch for asset changes (keep running in a terminal)
./build-assets.sh watch

# View logs
docker compose logs -f app
docker compose logs -f web

# Access Symfony console
docker compose exec app php bin/console

# Stop services
docker compose down
```

### Adding New Features

Follow this typical workflow:

1. **Create/Update Entity** (if needed)
   ```bash
   docker compose exec app php bin/console make:entity
   ```
   - MUST implement `CompanyOwnedInterface` if tenant-scoped
   - Use property hooks (PHP 8.4) for getters/setters
   - Add `declare(strict_types=1);` at the top

2. **Generate Migration**
   ```bash
   docker compose exec app php bin/console make:migration
   docker compose exec app php bin/console doctrine:migrations:migrate -n
   ```

3. **Create Service** (business logic)
   - Place in `src/Service/`
   - Use constructor property promotion
   - Keep controllers thin - delegate to services

4. **Create Controller** (presentation)
   ```bash
   docker compose exec app php bin/console make:controller
   ```
   - Use attribute routing (`#[Route('/path')]`)
   - Use security attributes (`#[IsGranted('ROLE_X')]`)
   - Minimal logic - delegate to services

5. **Create Form** (if UI needed)
   ```bash
   docker compose exec app php bin/console make:form
   ```

6. **Write Tests**
   - Unit tests for services (`tests/Unit/`)
   - Functional tests for controllers (`tests/Functional/`)

7. **Validate Code Quality**
   ```bash
   docker compose exec app composer check-all
   ```

### Running Tests

```bash
# All tests
docker compose exec app ./vendor/bin/phpunit

# By suite
docker compose exec app composer test-unit            # Unit tests
docker compose exec app composer test-functional      # Functional tests
docker compose exec app composer test-integration     # Integration tests
docker compose exec app composer test-api             # API tests
docker compose exec app composer test-e2e             # End-to-end (Panther)

# Single test file
docker compose exec app ./vendor/bin/phpunit tests/Unit/Service/MyServiceTest.php

# Code quality checks
docker compose exec app composer check-code           # PHPStan + PHP CS Fixer + PHPCS
docker compose exec app composer check-architecture  # Deptrac validation
docker compose exec app composer check-all           # Everything

# Fix code style automatically
docker compose exec app composer phpcsfixer-fix
```

**Note:** E2E tests use Panther (headless Chrome). If issues occur, set:
```bash
export PANTHER_CHROME_BINARY="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
export PANTHER_NO_SANDBOX=1
```

### Working with Database Migrations

```bash
# Create new migration
docker compose exec app php bin/console make:migration

# Apply migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n

# Rollback one migration
docker compose exec app php bin/console doctrine:migrations:migrate prev

# Check schema sync
docker compose exec app php bin/console doctrine:schema:validate

# Generate diff (if schema out of sync)
docker compose exec app php bin/console doctrine:migrations:diff
```

**Important:** There are 79 migrations in the project. Always create migrations for schema changes, never modify entities and run schema:update directly.

### Asset Development

```bash
# Development mode (watch for changes)
./build-assets.sh watch

# Development build (one-time)
./build-assets.sh dev

# Production build
./build-assets.sh prod

# Docker variants (if npm not installed locally)
./docker-build-assets.sh watch
./docker-build-assets.sh dev
./docker-build-assets.sh prod
```

**Asset Structure:**
- Entry points defined in `webpack.config.js`
- Source files in `assets/` (JS, SCSS, images, fonts)
- Compiled output in `public/assets/`
- RTL CSS automatically generated for internationalization

---

## Key Insights

### Code Quality Standards

This project enforces **strict quality standards**:

1. **PHPStan Level 3 + Strict Rules**
   - Static analysis catches type errors and bugs
   - Run before committing: `composer phpstan`

2. **PSR-12 + Symfony Coding Standards**
   - Enforced by PHP CS Fixer and PHPCS
   - Auto-fix: `composer phpcsfixer-fix`

3. **Architecture Validation (Deptrac)**
   - Prevents circular dependencies
   - Enforces layer boundaries
   - Run: `composer check-architecture`

4. **No Yoda Conditions**
   - Use `if ($var === 'value')` NOT `if ('value' === $var)`

### Security & Authorization

**Role Hierarchy:**
```
ROLE_USER (base - all authenticated users)
  └── ROLE_INTERVENANT (contributor/worker)
      └── ROLE_CHEF_PROJET (project manager)
          └── ROLE_MANAGER (team manager)
              └── ROLE_COMPTA (accounting)
                  └── ROLE_SUPERADMIN (full admin)
```

**Key Security Patterns:**
- All forms use CSRF protection (enabled by default)
- Controllers use `#[IsGranted('ROLE_X')]` attributes
- API endpoints use security expressions in `#[ApiResource]`
- Company-based data isolation via `CompanyVoter`
- 2FA is available but must be manually enabled per user

### Naming Conventions (from CONTRIBUTING.md)

**PHP:**
- Classes: `PascalCase` (ProjectController, ForecastingService)
- Methods: `camelCase` (createCampaign, calculateProgress)
- Variables: `camelCase` ($contributor, $yearlyStats)
- Constants: `SCREAMING_SNAKE_CASE` (ROLE_MANAGER, STATUS_ACTIVE)

**Database:**
- Tables: `snake_case` plural (performance_reviews, onboarding_tasks)
- Columns: `snake_case` (created_at, contributor_id)

**Routes:**
- Names: `snake_case` (performance_review_index, onboarding_team)
- URLs: `kebab-case` (/performance-reviews, /onboarding/team)

### Recent Major Features (from README)

1. **AI Planning Optimization (Nov 2024)**
   - TACE analysis with overload/underutilization detection
   - OpenAI (GPT-4o-mini) and Anthropic (Claude 3.5 Haiku) integration
   - Dashboard at `/planning/optimization`
   - Service-level-aware recommendations (VIP/Priority clients)

2. **Vacation Workflow**
   - Request/approval system with hierarchical validation
   - Real-time notifications via Symfony Messenger
   - Integration with planning calendar

3. **Client Service Levels**
   - 4 tiers: VIP, Prioritaire, Standard, Basse priorité
   - Auto-calculated based on annual revenue (Top 20 = VIP, Top 50 = Priority)
   - Manual override available
   - Command: `app:client:recalculate-service-level`

4. **Running Timer**
   - Start/stop timer from weekly timesheet
   - Only one active timer at a time
   - Auto-imputation with 0.125 day minimum

### Common Commands Beyond Basics

```bash
# Generate test data for a year
docker compose exec app php bin/console app:generate-test-data --year=2024

# Calculate analytics metrics
docker compose exec app php bin/console app:calculate-metrics 2024 --granularity=monthly
docker compose exec app php bin/console app:calculate-staffing-metrics 2024 --granularity=weekly

# Client service level calculation
docker compose exec app php bin/console app:client:recalculate-service-level

# Scheduler (for background tasks)
docker compose exec app php bin/console debug:scheduler
docker compose exec app php bin/console messenger:consume scheduler_default

# Message queue (async processing)
docker compose exec app php bin/console messenger:consume async -vv
docker compose exec app php bin/console messenger:failed:retry
```

### Important URLs

- Main app: http://localhost:8080
- Admin config: `/admin/technologies`, `/admin/service-categories`, `/admin/job-profiles`
- Planning: `/planning` (resource timeline)
- Planning optimization: `/planning/optimization` (AI recommendations)
- Analytics: `/analytics/dashboard`
- Staffing: `/staffing/dashboard`
- Vacation requests: `/vacation-request` (employees)
- Vacation approvals: `/vacation-approval` (managers)
- API docs: `/api/documentation` (Swagger/OpenAPI)
- 2FA setup: `/me/2fa/enable`

### Testing Philosophy

- **Unit tests:** Mock dependencies, test business logic in isolation
- **Functional tests:** Test HTTP responses, routing, form submissions
- **Integration tests:** Test database interactions, repositories
- **API tests:** Test REST endpoints via API Platform
- **E2E tests:** Test full user flows with Panther (browser automation)

**Test Environment:**
- Uses SQLite instead of MariaDB for isolation
- Configuration in `.env.test`
- Factories via Foundry for test data generation

---

## Dependencies & Integrations

### Key PHP Dependencies

**Core Framework:**
- `symfony/*` (v8.0) - Modern PHP framework with auto-wiring and auto-configuration
- `doctrine/orm` (v3.6) - Database ORM with migrations
- `doctrine/doctrine-migrations-bundle` - Version-controlled schema changes

**API & Admin:**
- `api-platform/core` (v4.2) - REST API with OpenAPI/Swagger docs
- `easycorp/easyadmin-bundle` (v4.27) - Admin CRUD interface
- `lexik/jwt-authentication-bundle` - JWT auth for API

**Security:**
- `scheb/2fa-bundle` + `scheb/2fa-totp` - Two-factor authentication (TOTP)
- `nelmio/security-bundle` - Security headers (CSP, HSTS, etc.)

**AI Integration:**
- `openai-php/client` - OpenAI GPT-4o-mini for planning optimization
- `anthropic-ai/sdk` - Anthropic Claude 3.5 Haiku for planning analysis
- `symfony/ai-*` bundles - Symfony AI abstraction layer

**Features:**
- `dompdf/dompdf` - PDF generation for invoices/reports
- `phpoffice/phpspreadsheet` - Excel export/import
- `symfony/messenger` + `symfony/redis-messenger` - Async job processing
- `symfony/scheduler` - Cron-like scheduled tasks
- `symfony/mailer` - Email notifications
- `sentry/sentry-symfony` - Error tracking and monitoring

**Development:**
- `phpstan/phpstan` (Level 3 + strict rules) - Static analysis
- `friendsofphp/php-cs-fixer` - Code style fixer
- `deptrac/deptrac` - Architecture validation
- `infection/infection` - Mutation testing
- `symfony/web-profiler-bundle` - Debug toolbar

### Frontend Dependencies

**Core:**
- `@hotwired/stimulus` - Lightweight JavaScript framework
- `@hotwired/turbo` - SPA-like navigation without full SPA complexity
- `@symfony/ux-live-component` - Live updating components
- `bootstrap` 5.3.8 - UI framework (Skote theme)

**Enhancements:**
- `choices.js` - Enhanced select dropdowns
- `toastr` - Toast notifications
- `fullcalendar` - Planning/calendar views (included in libs)

### External Services

**Required:**
- **MariaDB 11.4** - Primary database (Docker container)
- **Redis** - Cache + message queue transport (Docker container)

**Optional (for full features):**
- **OpenAI API** - AI planning recommendations (requires API key)
- **Anthropic API** - Alternative AI provider (requires API key)
- **Sentry** - Error tracking (requires DSN)
- **SMTP Server** - Email notifications (configure in `.env`)

### Configuration Files

**Environment Variables:**
- `.env` - Default values (committed)
- `.env.local` - Local overrides (gitignored)
- `.env.dev` - Development-specific (committed)
- `.env.dev.local` - Local dev overrides (gitignored)
- `.env.test` - Test environment (SQLite instead of MariaDB)
- `.env.render.example` - Example for Render.com deployment

**Key Environment Variables:**
- `DATABASE_URL` - Database connection
- `REDIS_URL` - Redis connection
- `MAILER_DSN` - Email configuration
- `OPENAI_API_KEY` - OpenAI API key (optional)
- `ANTHROPIC_API_KEY` - Anthropic API key (optional)
- `SENTRY_DSN` - Sentry error tracking (optional)
- `APP_ENV` - Environment (dev/prod/test)
- `APP_SECRET` - Symfony secret for CSRF/sessions

---

## Open Questions & Uncertainties

### Things to Clarify with the Team

1. **Company Context Handling:**
   - How is the current company determined for logged-in users?
   - Is there a middleware/service that sets this automatically?
   - What happens if a user belongs to multiple companies?

2. **Business Units:**
   - The `BusinessUnit` entity exists with a feature flag
   - How mature is this feature? Is it production-ready?
   - Are there any gotchas when enabling `FEATURE_BUSINESS_UNITS`?

3. **Default Tasks (AVV, Non-vendu):**
   - What do AVV and Non-vendu stand for?
   - Why are these automatically created for new projects?
   - What's the semantic difference vs regular ProjectTasks?

4. **Timesheet → ProjectTask Relationship:**
   - README mentions this is optional - when should it be used vs not?
   - What calculations exclude AVV/Non-vendu tasks?
   - Is there documentation on the profitability calculation logic?

5. **SaaS Features:**
   - The Company entity has subscription tiers and feature flags
   - Is this actively used or is it planned for future multi-tenancy?
   - Are there any working examples of tier-based feature restrictions?

6. **AI Integration Maturity:**
   - How reliable are the AI planning recommendations?
   - What's the fallback behavior if API keys are missing?
   - Are there rate limiting considerations?

7. **Analytics Caching Strategy:**
   - Redis is used for caching analytics
   - What's the cache invalidation strategy?
   - Are there any cache warming scripts?

### Potential Gotchas Discovered

1. **PHP 8.5 Requirement:** Composer.json specifies `php: >=8.5` which is cutting edge - ensure Docker image matches
2. **79 Migrations:** Large migration history - pulling latest code may require running many migrations
3. **Asset Build Required:** Application won't work properly without building assets first
4. **Test Environment Different:** SQLite in tests vs MariaDB in dev - subtle differences may cause issues
5. **Property Hooks:** PHP 8.4 property hooks are used heavily - ensure IDE/tooling supports this syntax
6. **Deptrac Parser Issues:** Currently excludes Scheduler files due to PHP 8.4 syntax parsing issues

### Areas Needing Investigation

- **Performance Optimization:** Any caching strategies beyond Redis?
- **Deployment Process:** What's the production deployment workflow?
- **Backup Strategy:** How are database backups handled?
- **Monitoring:** Beyond Sentry, what monitoring is in place?
- **CI/CD Pipeline:** Details of the GitHub Actions workflow

---

## Additional Resources

- **README.md** - Installation and feature overview
- **CONTRIBUTING.md** - Detailed contribution guidelines and code standards
- **CLAUDE.md** - Commands reference and project-specific patterns for AI assistants
- **WARP.md** - WARP index for project navigation
- **AGENTS.md** - Agent guidelines
- **docs/** - Technical documentation
- **GitHub Actions:** `.github/workflows/ci.yml` - CI pipeline configuration

---

*This guide is a living document. Last updated: January 6, 2026*
