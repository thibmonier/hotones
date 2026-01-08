# HotOnes - Project Evolution Timeline

**Analysis Date**: January 6, 2026

**Repository**: HotOnes - Web Agency Project Profitability Management System

---

## Overview

HotOnes is a comprehensive web agency project management and profitability tracking system built with Symfony 8.0 and PHP 8.4. The application helps agencies analyze profitability by cross-referencing sales data (days/daily rates), time tracking, costs, purchases, and consolidated KPIs. The project has evolved from a basic project management system to a sophisticated multi-tenant SaaS platform with AI-powered planning optimization.

**Current Stack** (as of January 2026):
- Backend: Symfony 8.0, PHP 8.4
- Database: MariaDB 11.4
- Frontend: Twig + Bootstrap 5 (Skote theme)
- Assets: Webpack Encore + Sass
- API: ApiPlatform 4 (REST)
- Authentication: 2FA with scheb/2fa-bundle (TOTP)

**Total Commits**: 532 commits spanning from October 19, 2025 to January 6, 2026

---

## Origin

**Initial Commit**: October 19, 2025, 23:05 by Thibaut MONIER

The HotOnes project was created as a Symfony 7.3 (later upgraded to 8.0) web application with a focus on project profitability management for web agencies. The initial commit set up a foundational Symfony project with:

- PHP 8.2+ requirement (later upgraded to 8.4, then 8.5)
- Symfony 7.3.* framework
- Security with 2FA (scheb/2fa-bundle, scheb/2fa-totp)
- Doctrine ORM for database management
- Webpack Encore for asset management
- QR code support (endroid/qr-code-bundle)

**Initial Purpose**: The project aimed to help web agencies track and analyze project profitability by combining sales data, time tracking, costs, and KPIs into a unified management system.

**First Week** (October 19-31, 2025):
- PHPStan configuration for static analysis
- Basic admin CRUD interfaces for technologies, profiles, and service categories
- Project sub-task management with Kanban view
- User management with avatars and career sections
- Twig Components integration
- Asset management improvements

The project quickly established key patterns: comprehensive testing (PHPUnit), code quality tools (PHPStan, PHP CS Fixer), and a structured approach to development with clear commit messages documenting "what" changed.

---

## Timeline of Evolution

### Phase 1: Foundation & Core Features (October-November 2025)

**October 19 - November 8, 2025**: Establishing the Foundation

The initial weeks focused on building core project management functionality:

- **What changed**: Basic Symfony project setup with entities for Contributors, Projects, Orders (quotes), Timesheets, and administrative data
- **Key additions**:
  - PHP 8.4 upgrade (October 30)
  - Test infrastructure with factories (Zenstruck Foundry) and fixtures
  - CI/CD pipeline with GitHub Actions
  - E2E testing with Symfony Panther
  - SQLite for test environment (isolation without external DB)
  - Advanced project filtering, pagination, and KPI tracking
  - Contract types and payment schedules
  - Global billing view with navigation

- **Why**: The foundation needed to be solid with testing and code quality from day one, establishing patterns for future development
- **Impact**: Set the tone for a quality-focused, well-tested codebase with comprehensive documentation

### Phase 2: Analytics & Dashboards (November-December 2025)

**November 8 - December 10, 2025**: Data-Driven Decision Making

Focus shifted to providing insights through analytics and visualization:

- **What changed**:
  - Complete analytics dashboard with star schema implementation
  - Excel export capabilities
  - Scheduled metrics recalculation
  - Treasury dashboard with comprehensive financial tracking
  - Predictive analytics (Sprint 8-9)
  - Project satisfaction evolution tracking
  - Chart.js lazy loading (40% initial load reduction)

- **Why**: Agencies need real-time visibility into profitability, project health, and financial forecasts to make informed business decisions
- **Impact**: Transformed the application from a data entry tool to a strategic business intelligence platform

### Phase 3: Planning & Resource Management (November-December 2025)

**November-December 2025**: Intelligent Resource Allocation

Introduction of sophisticated planning and optimization features:

- **What changed**:
  - Resource timeline with FullCalendar Scheduler
  - Leave/vacation workflow with hierarchical approval
  - **AI-powered planning optimization** (major milestone)
  - TACE (Taux d'Activité Contributeur Effectif) analysis
  - Intelligent recommendations using OpenAI (GPT-4o-mini) and Anthropic (Claude 3.5 Haiku)
  - Service level management (VIP, Priority, Standard, Low Priority)
  - Automatic service level calculation based on annual revenue
  - Manager dashboard widgets for vacation approvals

- **Why**: Resource planning is critical for agency profitability - the AI integration helps identify overloads, underutilization, and optimization opportunities automatically
- **Impact**: Elevated the platform from manual planning to AI-assisted strategic resource management, with automated recommendations considering client service levels and project priorities

### Phase 4: SaaS Transformation & Public Presence (December 2025)

**December 17-31, 2025**: Opening to the World

Major architectural shift toward multi-tenant SaaS:

- **What changed**:
  - Public website with SaaS presentation pages
  - Modern dark theme redesign for public pages
  - HTTP Basic Auth for public access protection
  - SEO tools: GA4 integration, sitemap generation, meta tags
  - GDPR/RGPD compliance foundation (Lot 27)
  - Cookie consent management
  - Data export and account deletion workflows
  - Professional email templates
  - Custom error pages (404 with branding)
  - Flysystem integration with Cloudflare R2 for file storage

- **Why**: To transform from a single-use internal tool to a marketable SaaS product requiring public-facing features, compliance, and professional presentation
- **Impact**: Positioned HotOnes as a commercial SaaS offering rather than just an internal tool

### Phase 5: Multi-Tenant Migration (December 31, 2025 - January 4, 2026)

**Lot 23: The Great Multi-Tenant Migration** - The Most Significant Architectural Change

This was the largest and most complex transformation in the project's history:

- **What changed**:
  - **Phase 2.4**: Core multi-tenant backend infrastructure
  - **Phase 2.6**: Reversible database migrations with backup/restore scripts
  - **Migrations 3-10**: Added `company_id` to all 54+ entities in 8 batches:
    - Batch 1: Contributors, employment periods, profiles, skills
    - Batch 2: Clients, projects, contacts, tasks
    - Batch 3: Orders
    - Batch 4: Timesheets & planning
    - Batch 5: Reference data
    - Batch 6: Analytics
    - Batch 7: 22 business-critical tables
    - Batch 8: 13 final tables
  - **Phase 2.7**: All 54 entities implementing `CompanyOwnedInterface`
  - **Phase 2.8**: Company scoping in 45+ repositories (3 batches)
  - **Phase 2.9**: Company scoping in services (Analytics, Metrics, core business services)
  - **Controller migration**: All 21 critical controllers updated with `CompanyContext` injection
  - **Test infrastructure**: Complete rewrite of test suite for multi-tenant support

- **Why**: To support multiple companies/agencies using the same application instance, with complete data isolation between tenants - essential for a SaaS business model
- **Impact**:
  - Enabled true multi-tenancy with guaranteed data isolation
  - Required extensive testing infrastructure updates
  - All forms now automatically assign Company context
  - Repositories filter by company automatically
  - 100% controller coverage for Company assignment (21/21 controllers)
  - Security-critical change: prevents data leakage between tenants

**Technical Challenges**:
- Coordination across 54 entities
- Ensuring referential integrity with foreign keys
- Batch migration strategy to minimize risk
- Test suite compatibility (fixed all test failures)
- PHP 8.4 property hooks compatibility issues (resolved with compatibility getters/setters)

### Phase 6: Consolidation & Optimization (December 2025 - January 2026)

**Lot 11bis: Technical Sprint & Consolidation**

- **What changed**:
  - PHPStan level 4 achievement (from level 1)
  - PHP 8.5.1 upgrade in Docker images
  - Doctrine optimizations with stof/doctrine-extensions
  - doctrine-doctor bundle integration
  - Infection mutation testing framework
  - Deptrac architectural layer validation
  - Docker health checks and resource limits
  - Redis optimization for caching
  - Render deployment optimizations (50-60% faster)
  - Comprehensive unit test additions (coverage improvements)
  - Flaky test fixes (Infection with default test order)

- **Why**: After rapid feature development and the massive multi-tenant migration, consolidation was necessary to ensure code quality, performance, and maintainability
- **Impact**:
  - Improved code quality and type safety
  - Better performance in production
  - More reliable testing
  - Faster deployments
  - Architectural boundaries enforced

### Phase 7: AI Integration & Modern Features (December 2025 - January 2026)

**December 21 - January 6, 2026**: Embracing AI

Latest evolution bringing AI capabilities throughout the application:

- **What changed**:
  - **Marvin chatbot widget** with Claude 3.5 Haiku (December 21)
  - **Symfony AI Bundle** implementation (Sprint 1/4, December 30)
  - **AI Tools** for context-aware quote generation (Sprint 2/4)
  - **Documentation search tool** with AI (Sprint 3/4)
  - Anthropic set as primary AI provider (January 5, 2026)
  - AI-powered planning optimization (already mentioned in Phase 3 but ongoing refinement)

- **Why**: AI can assist users with complex tasks like quote generation, provide intelligent help through chat, and optimize resource planning
- **Impact**: Made the application more intelligent and user-friendly, reducing cognitive load for complex operations

---

## Patterns & Approaches

### Current Approaches (Modern Patterns)

#### 1. Multi-Tenant Architecture (Since December 31, 2025)
**Pattern**: Every entity implements `CompanyOwnedInterface` and stores `company_id`
```php
interface CompanyOwnedInterface {
    public function getCompany(): ?Company;
    public function setCompany(Company $company): self;
}
```
- All repositories automatically filter by company
- All controllers inject `CompanyContext` to get current company
- Forms automatically assign company on entity creation
- Complete data isolation between tenants

#### 2. PHP 8.4+ Property Hooks (December 2025 - January 2026)
**Pattern**: Modern PHP 8.4 property hooks with compatibility layer
- Entities use property hooks for cleaner code
- Compatibility getters/setters added for libraries expecting traditional methods
- Factories updated to work with property hooks

#### 3. AI-Powered Features (December 2025 - January 2026)
**Pattern**: Symfony AI Bundle integration with multiple providers
- OpenAI (GPT-4o-mini) for planning optimization
- Anthropic (Claude 3.5 Haiku) for chatbot and general AI tasks
- AI Tools abstraction for reusable AI capabilities
- Context-aware generation (quotes, documentation search)

#### 4. Testing Strategy (Since November 2025)
**Pattern**: Comprehensive multi-layered testing
- Unit tests with PHPUnit
- Functional tests (controller/integration testing)
- E2E tests with Symfony Panther
- Test fixtures with Zenstruck Foundry factories
- SQLite for test database (fast, isolated)
- Mutation testing with Infection
- Repository test coverage prioritized

#### 5. Code Quality Enforcement (Continuous)
**Pattern**: Multiple quality gates
- PHPStan level 4 static analysis
- PHP CS Fixer for code style
- Deptrac for architectural boundaries
- CI/CD pipeline runs all checks
- Pre-push hooks with githooks

#### 6. Asset Management (Since October 2025)
**Pattern**: Webpack Encore + Sass
- Build scripts for dev/prod (`build-assets.sh`)
- Docker-based builds (`docker-build-assets.sh`)
- Watch mode for development
- Lazy loading for performance (Chart.js dashboards)

#### 7. File Storage (December 2025)
**Pattern**: Flysystem with cloud storage
- Cloudflare R2 integration
- LiipImagine for image processing with Flysystem cache
- Abstracted storage layer (can switch providers)

#### 8. Structured Development (Since October 2025)
**Pattern**: "Lot" and "Sprint" based development
- Features organized in "Lots" (Lot 9, Lot 11bis, Lot 23, Lot 27, etc.)
- Sprints for iterative development within Lots
- Clear progress tracking in commit messages
- Documentation of each phase

### Legacy Approaches (Replaced or Deprecated)

#### 1. Single-Tenant Architecture (Before December 31, 2025) ❌ DEPRECATED
**Old Pattern**: No company context, single organization per instance
- Entities had no company_id
- No data isolation
- Controllers didn't inject CompanyContext
- Repositories returned all data

**Migration**: Lot 23 Multi-Tenant Migration completely replaced this

#### 2. Traditional Getters/Setters (Before December 2025) ⚠️ COMPATIBILITY LAYER
**Old Pattern**: Explicit getter/setter methods
```php
private string $name;
public function getName(): string { return $this->name; }
public function setName(string $name): self { $this->name = $name; return $this; }
```

**New Pattern**: PHP 8.4 property hooks with compatibility
```php
public string $name {
    get => $this->name;
    set => $this->name = $value;
}
// Compatibility methods still present for Doctrine/Forms
```

#### 3. APCu for System Cache (December 2025) ❌ REPLACED
**Old Pattern**: APCu for production caching
**Problem**: Build errors in containerized environments
**New Pattern**: Redis for system cache (more reliable in Docker/production)

#### 4. Manual Resource Planning (Before November 2025) ⚠️ SUPPLEMENTED
**Old Pattern**: Manual resource allocation without recommendations
**New Pattern**: AI-powered optimization with TACE analysis and intelligent suggestions
- Old manual approach still available
- AI recommendations supplement human decision-making

#### 5. Local File Storage (Before December 2025) ❌ REPLACED
**Old Pattern**: Files stored in `public/uploads/`
**New Pattern**: Flysystem with Cloudflare R2 cloud storage
- More scalable for SaaS
- Better for multi-tenant with large file volumes

#### 6. Manual Service Level Assignment (Before November 2025) ⚠️ SUPPLEMENTED
**Old Pattern**: Manual client priority management
**New Pattern**: Automatic calculation based on revenue (Top 20 = VIP, Top 50 = Priority)
- Manual override still possible
- Recalculation command available

---

## Notable Files & Components

### Critical Multi-Tenant Files (January 2026)
- `src/Security/CompanyContext.php` - Provides current company for logged-in user
- `src/Entity/Company.php` - The tenant entity
- `src/Security/CompanyOwnedInterface.php` - Interface all tenant-aware entities implement
- `MIGRATION-COMPLETE.md` - Documents completed multi-tenant migration
- `docs/multi-tenant-company-context-migration.md` - Technical migration guide

### AI Integration Files (December 2025 - January 2026)
- `src/AI/` - AI tools and integrations directory
- `templates/components/chatbot/marvin.html.twig` - Marvin chatbot widget
- Planning optimization controller and services

### Core Business Logic
- `src/Entity/` - 54+ entities (all with CompanyOwnedInterface)
  - Key entities: Contributor, Project, Order, Timesheet, Invoice, Client, Planning
- `src/Repository/` - 45+ repositories (all with company filtering)
- `src/Service/` - Business services (MetricsCalculationService, analytics services, etc.)
- `src/Controller/` - 66 controllers (21 updated for multi-tenant in Lot 23)

### Configuration & Infrastructure
- `composer.json` - PHP 8.4+, Symfony 8.0, extensive bundle ecosystem
- `docker-compose.yml` - Multi-container setup (app, nginx, db, redis)
- `Dockerfile` - Multiple variants (dev, optimized, simple)
- `phpstan.neon` - Level 4 static analysis configuration
- `phpunit.xml.dist` - Test suite configuration
- `deptrac.yaml` - Architectural layer boundaries
- `infection.json5` - Mutation testing configuration

### Documentation (Extensive)
- `docs/` - Organized into 11 categories:
  - 01-getting-started
  - 02-architecture
  - 03-features
  - 04-development
  - 05-deployment
  - 06-security
  - 07-performance
  - 08-ui-ux
  - 09-migration
  - 10-planning
  - 11-reports
- `README.md` - Comprehensive project overview
- `CLAUDE.md` - Guide for AI assistants working on the project
- `AGENTS.md` - Agent-specific documentation
- `WARP.md` - WARP index for navigation
- `CONTRIBUTING.md` - Contribution guidelines

### Migration & Scripts
- `migrations/` - 79 database migrations
- `backups/` - Database backup scripts
- `scripts/` - Automation scripts
- `fix-company-context.php` - Multi-tenant migration helper
- `apply-company-fixes.sh` - Automated fix application
- `add-constructors.py` - Constructor injection automation

---

## Key Insights

### 1. Rapid Evolution with Quality Focus
The project went from initial commit to production-ready multi-tenant SaaS in just **78 days** (Oct 19 - Jan 5), maintaining high code quality throughout with PHPStan level 4, comprehensive testing, and architectural validation.

### 2. AI-First Modern Application
HotOnes embraced AI early and comprehensively:
- Planning optimization with multiple AI providers
- Chatbot for user assistance
- Context-aware quote generation
- Documentation search
- Demonstrates modern 2025-2026 development practices

### 3. Multi-Tenant as Architectural Pivot
The Lot 23 migration (Dec 31 - Jan 4) was the most significant architectural change:
- 54+ entities updated
- 45+ repositories modified
- 21 controllers refactored
- Complete test suite overhaul
- Shows commitment to SaaS model over single-tenant

### 4. Structured Agile Approach
Development organized in clear "Lots" and "Sprints":
- Lot 9: UI standardization
- Lot 11bis: Technical consolidation
- Lot 15.5: Form standardization
- Lot 17: Naming improvements
- Lot 20: Error pages
- Lot 23: Multi-tenant (the big one)
- Lot 27: GDPR compliance
- Each lot has clear objectives and completion criteria

### 5. Production-Ready DevOps
Sophisticated deployment setup:
- Docker multi-stage builds
- Render.com production deployment
- Health checks and resource limits
- Redis caching
- Cloudflare R2 CDN
- 50-60% deployment time reduction through optimization

### 6. Comprehensive Testing Culture
Testing embedded from week 1:
- 532 commits with maintained test suite
- Multiple test types (unit, functional, E2E)
- Mutation testing (Infection)
- Test factories for reliable fixtures
- SQLite for fast isolated tests

### 7. Documentation as First-Class Citizen
Exceptional documentation:
- 11 documentation categories
- Multiple markdown guides (CLAUDE.md, AGENTS.md, WARP.md)
- Migration documentation
- API documentation (Swagger)
- Shows professional, maintainable approach

### 8. Modern PHP Adoption
Bleeding edge PHP usage:
- PHP 8.4 property hooks
- PHP 8.5.1 in production
- Symfony 8.0
- Demonstrates willingness to use latest features

### 9. Security & Compliance
Enterprise-ready security:
- 2FA (TOTP)
- GDPR/RGPD compliance
- Cookie consent management
- HTTP Basic Auth for protection
- Security bundle configuration
- CSP (Content Security Policy)

### 10. Analytics & Business Intelligence Focus
Not just CRUD, but strategic tool:
- Predictive analytics
- Treasury dashboard
- KPI tracking
- Profitability metrics
- Resource optimization
- Star schema for analytics queries

---

## Open Questions & Uncertainties

### 1. Original Business Context
**Question**: What specific pain points led to creating HotOnes?
- Was there a previous system being replaced?
- What manual processes were being automated?
- Why was an off-the-shelf solution not suitable?

**What we know**: Created by Thibaut MONIER in October 2025 for web agency profitability management, but the specific catalyst is unclear.

### 2. The Gap in Early History
**Question**: Why was the project so well-structured from day one?
- Was there prior planning/design phase not in Git history?
- Was this a rewrite of an earlier system?
- The initial commit already had sophisticated setup (2FA, QR codes, Webpack)

**Observation**: Most projects start simpler; this one had enterprise features from commit #1.

### 3. Multi-Tenant Timing
**Question**: Why was multi-tenancy not part of the initial design if SaaS was the goal?
- The Lot 23 migration was massive - why not build multi-tenant from the start?
- Was the SaaS transformation a pivot, or always planned?

**Best guess**: Likely started as single-company tool, then opportunity for SaaS emerged in December, triggering the architectural shift.

### 4. AI Provider Strategy
**Question**: Why both OpenAI and Anthropic?
- January 5 commit set Anthropic as "primary" - was OpenAI having issues?
- Cost optimization? Feature differences?

**What we know**: Both are integrated; Anthropic became primary on Jan 5, 2026.

### 5. Production Usage & Customers
**Question**: Is HotOnes in production with real customers?
- Render deployment is set up
- Multi-tenant suggests multiple tenants
- But no visible customer names or production metrics in commits

**Uncertainty**: Can't determine from Git history if this is live with paying customers or still pre-launch.

### 6. The "Lot" Numbering
**Question**: Why Lot 9, 11bis, 15.5, 17, 20, 23, 27?
- Non-sequential numbering suggests missing lots
- What were Lots 1-8, 10-14, 16, 18-19, 21-22, 24-26?
- Were they completed earlier? Cancelled? Planned?

**Observation**: Only certain lot numbers appear in commit history.

### 7. PHP 8.5 Adoption Timing
**Question**: PHP 8.5.1 was adopted December 28, 2025 - was this necessary or experimental?
- Property hooks are in PHP 8.4, so 8.5 not required for those
- Was there a killer feature in 8.5?
- Potential risk using such new PHP version?

**Observation**: Project is very aggressive with version adoption.

### 8. Test Coverage Percentages
**Question**: What is the actual test coverage?
- Commits mention "6.70% to 7.84%" (December 27)
- But project has extensive tests since November
- Are those percentages accurate for whole codebase?

**Uncertainty**: Coverage metrics seem low for a project emphasizing testing.

### 9. "HotOnes" Name Origin
**Question**: Why "HotOnes"?
- Is it a reference to the YouTube show "Hot Ones"?
- Does it have meaning related to web agencies/profitability?
- Just a fun name?

**No evidence in repository to answer this.**

### 10. Future Roadmap
**Question**: What's planned after Lot 23 and multi-tenant completion?
- Repository has `ROADMAP.md` but we haven't examined it
- What are the next major features?
- When is "version 1.0" or general availability?

**Note**: This could be answered by reading the roadmap document.

---

## Summary

HotOnes represents a **rapid, quality-focused evolution** from single-tenant project management tool to multi-tenant AI-powered SaaS platform in just 78 days. The most significant architectural change was the Lot 23 Multi-Tenant Migration (Dec 31 - Jan 4, 2026), which touched 54+ entities, 45+ repositories, and 21 controllers. The project demonstrates modern development practices: comprehensive testing, static analysis, AI integration, cloud-native deployment, and extensive documentation. The structured "Lot" approach to development, combined with bleeding-edge PHP/Symfony versions, shows both discipline and technical ambition.

**Key Transformation Arc**:
1. **Foundation** (Oct-Nov): Core features + testing infrastructure
2. **Intelligence** (Nov-Dec): Analytics, dashboards, AI optimization
3. **Commercialization** (Dec): Public site, GDPR, professional features
4. **Multi-Tenancy** (Dec 31-Jan 4): Complete architectural transformation
5. **Consolidation** (Dec-Jan): Quality, performance, stability
6. **AI Integration** (Dec-Jan): Chatbot, AI tools, intelligent features

The project is now positioned as an **enterprise-ready, AI-powered, multi-tenant SaaS platform** for web agency profitability management.

---

*Analysis completed: January 6, 2026 at 16:46 GMT+1*
*Repository: /Users/tmonier/Projects/hotones*
*Total commits analyzed: 532*
*Time span: October 19, 2025 - January 6, 2026 (78 days)*
