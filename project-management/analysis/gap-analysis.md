# Gap Analysis Report — HotOnes

```
+==============================================================+
|                   GAP ANALYSIS REPORT                          |
+==============================================================+
| Project: HotOnes                                               |
| Analysis Date: 2026-05-04                                      |
| Last update:   2026-05-04 (post atelier — GAP-C3 + GAP-C5 ✅)  |
| Scope: full repository                                         |
| Branch: chore/housekeeping-001-doc-bundle                      |
+==============================================================+
| Inputs:                                                        |
| - project-management/scan-report.md                            |
| - project-management/prd.md (DRAFT)                            |
| - project-management/backlog/user-stories/INDEX.md (DRAFT)     |
+==============================================================+
| Coverage snapshot:                                             |
| - Spec → Code: ~96% (PRD inferred from code, near 1:1)         |
| - Code → Spec: ~78% (multiple controllers/entities unmapped)   |
| - Test (file-ratio): ~23% (102 tests for 446 src files)        |
+==============================================================+
| Gaps found: 21                                                 |
| - Critical: 3                                                  |
| - Major:    8                                                  |
| - Minor:    7                                                  |
| - Info:     3                                                  |
+==============================================================+
```

> **Reading guide**: PRD was reverse-engineered from the code, so "spec without code" is mostly empty — that's an artefact of the inference, not a real gap. The valuable findings live in **§B (code without spec)**, **§C (mismatches)** and **§D (test gaps)**.

---

## A. Spec without Code (Not Implemented)

| ID | FR / US | Severity | Evidence |
|----|---------|----------|----------|
| GAP-A1 | PRD §3 Goals & Metrics — entire section is `PLACEHOLDER` (no OKR, no activation/retention KPI defined) | Minor | `prd.md` §3 explicit placeholder |
| GAP-A2 | PRD §10 Glossary — only skeleton; no ubiquitous-language formalisation | Minor | `prd.md` §10 |
| GAP-A3 | FR-IAM-05 multi-tenant **enforcement** missing (claim ≠ implementation) — see §C, GAP-C1 (counted there) | — | cross-ref |

> Net new "spec-without-code" items: **2** (both PO-side documentation gaps, no engineering blocker).

---

## B. Code without Spec (Undocumented in PRD/US)

### B.1 Controllers without explicit FR/US mapping

| GAP | Controller | Routes (approx) | Likely role | Severity | Recommendation |
|-----|------------|-----------------|-------------|----------|----------------|
| GAP-B1 | `Controller/Admin/BackofficeDashboardController` | `/backoffice` | Tenant admin home dashboard | Minor | Add FR-OPS-08 "Backoffice landing dashboard" or fold into FR-AN-02. |
| GAP-B2 | `Controller/Admin/CompanyCrudController` | `/admin/companies` | Tenant superadmin companies CRUD | Major | Add FR-IAM-10 "Tenant (Company) management" — important for SUPERADMIN scope. |
| GAP-B3 | `Controller/Admin/CompanySettingsController` | `/admin/company-settings` | Tenant config (branding, finance, locale, etc.) | Major | Add FR-IAM-11 "Tenant settings" — drives branding, defaults, currency, VAT. |
| GAP-B4 | `Controller/Admin/SkillCrudController`, `Controller/Admin/TechnologyCrudController` | `/admin/skills` `/admin/technologies` | Taxonomy admin for HR/projects | Minor | Add FR-HR-09 "Skills/Technologies taxonomy admin". |
| GAP-B5 | `Controller/Admin/ProfileCrudController` | `/admin/profiles` | Backoffice profile CRUD (likely vs `Profile` entity = job title catalog) | Minor | Add FR-HR-10 "Profile catalog" or merge with B4 as taxonomy. |
| GAP-B6 | `Controller/Api/DashboardController` | `/api/...` | API-side dashboard data | Minor | Tie to FR-AN-02 as API surface. |
| GAP-B7 | `Controller/Api/DependentFieldsController` | `/api/dependent-fields` | Live form dependent fields | Info | Sub-capability of FR-OPS-07 — fold into US-085. |
| GAP-B8 | `Controller/Api/TaskApiController` | `/api/tasks/*` | API CRUD for tasks | Major | Add FR-PRJ-10 "Project tasks API" — currently only web FR-PRJ-01 covers tasks. |
| GAP-B9 | `Controller/Api/PredictionsController` (already linked to FR-AN-03) | `/api/predictions` | Predictions API | Info | Documented under §5.10 — low gap, just confirm in PRD link. |
| GAP-B10 | `Controller/Api/ApiDocController` | `/api/documentation` | API docs UI | Info | Implicit FR-IAM-02. Low priority. |
| GAP-B11 | `BadgeController`, `LeaderboardController` (mapped FR-HR-08 but routes not enumerated) | `/badges/*` `/leaderboard` | Gamification surfaces | Minor | Confirm FR-HR-08 covers all badge/leaderboard routes. |
| GAP-B12 | `ErrorTestController` | `/{403,404,500}` | Dev-only error trigger | Info | Document as dev tool; gate behind `kernel.environment=dev`. |
| GAP-B13 | `LeadMagnetController` overlap with `CrmLeadController` and `LeadCapture` | various | Lead capture funnel | Minor | Already flagged in `INDEX.md` overlaps. Consolidate FRs. |
| GAP-B14 | `Controller/Staffing/*` content not enumerated in scan | `/...` | Staffing surfaces | Minor | Drill into folder; tie to FR-PLN-02 / FR-PLN-03. |

### B.2 Entities without explicit FR/US mapping

| GAP | Entity | Severity | Recommendation |
|-----|--------|----------|----------------|
| GAP-B15 | `Provider` | Minor | Add FR for fournisseur management (sale-side vs HR providers). Differentiate from `SaasProvider`. |
| GAP-B16 | `Vendor` | Minor | Same — clarify lexicon (Provider vs Vendor) and create FR. |
| GAP-B17 | `BusinessUnit` (linked to Company multi-tenant) | Major | Add FR-IAM-12 "Business Unit management" — mentioned in personas/scope but no explicit FR. Drives staffing scope and KPIs by BU. |
| GAP-B18 | `src/Entity/Analytics/{DimContributor,DimProfile,DimProjectType,DimTime,FactProjectMetrics,FactStaffingMetrics}` — full star-schema | Major | Add FR-AN-06 "Analytics data warehouse (dim/fact)" — currently only `FactForecast` is referenced in PRD. Document ETL pipeline + refresh cadence. |
| GAP-B19 | `OrderTask` and `ProjectTask` linkage at signing time | Minor | Behaviour referenced in US-016 acceptance criteria but no explicit FR for the conversion job. |
| GAP-B20 | `FactProjectMetrics` / `FactStaffingMetrics` aggregations not surfaced as a FR | Major | (combined with B18) |

### B.3 Console commands without spec

| GAP | Command | Severity | Recommendation |
|-----|---------|----------|----------------|
| GAP-B21 | 46 console commands under `src/Command` not catalogued in PRD | Minor | Index in a new `prd.md §X Operations CLI` section listing each `app:*` command + purpose. |

> **Net code-without-spec**: **~21 candidates** (incl. star-schema). 4 are **Major** (B2, B3, B8, B17, B18/B20).

---

## C. Spec-Code Mismatches (Inconsistencies)

### GAP-C1 🔴 CRITICAL — Multi-tenant isolation NOT enforced at Doctrine level

**Severity**: Critical (security / data integrity)

**Spec claim**: README "Support multi-sociétés avec **isolation complète des données**" + PRD FR-IAM-05.

**Code reality**:
```bash
# Search performed by /project:gap-analysis:
grep -r "(SQLFilter|TenantFilter|TenantContext|TenantAware)" src --include="*.php" -l  # → 0 matches
grep "filters:" config/packages/doctrine.yaml                                          # → 0 matches
find src -type d -name "Filter"                                                        # → 0 matches
```

There is **no Doctrine SQLFilter** for tenant scope. Isolation, if any, relies on per-repository `WHERE company = :tenant` discipline — **not centrally enforced**. Risk of cross-tenant data exposure on any new repository method that omits the filter.

`.claude/rules/14-multitenant.md` prescribes: `TenantFilter extends SQLFilter` + `TenantContext` + `TenantMiddleware` + `TenantAwareTrait`. None of these exist.

**Recommendation**:
1. Implement `TenantContext`, `TenantAwareTrait`, `TenantFilter` as per project rules.
2. Backfill on all tenant-scoped entities (~50 / 63).
3. Add cross-tenant regression tests (Alice user-A queries Bob's company-B → must be empty).
4. Audit each repository for raw `WHERE company = ` joins — replace with filter-driven scope.

**Effort**: L (>8h) — multi-sprint epic.
**Priority**: must precede any production exposure beyond pilot tenants.

---

### GAP-C2 🔴 CRITICAL — Voter coverage thin

**Severity**: Critical → Major (currently no production CVE evidence; structural gap)

**Spec claim**: PRD NFR-SEC + role hierarchy + multi-tenancy.

**Code reality**:
```
src/Security/Voter/CompanyVoter.php   ← 1 voter only
#[IsGranted(...)] usage:               ← 127 occurrences in controllers
denyAccessUnlessGranted(...) usage:    ← 2 occurrences
```

127 `#[IsGranted]` checks rely on **role names**, not on **entity ownership**. Combined with C1, that means a `ROLE_CHEF_PROJET` from tenant A could potentially read `Project` of tenant B if no repository filter and no entity-level voter blocks them.

**Recommendation**:
1. Define entity voters at minimum for: `Project`, `Order`, `Invoice`, `Timesheet`, `Contributor`, `Vacation`, `Client`, `ExpenseReport`.
2. Each voter must verify (a) tenant match, (b) role grant, (c) ownership/assignment.
3. Replace key `#[IsGranted("ROLE_X")]` checks on resource routes with `#[IsGranted("VIEW", subject: "project")]` etc.
4. Add tests for cross-tenant + cross-role denial.

**Effort**: L (>8h).

---

### GAP-C3 ✅ RESOLVED — `ROLE_COMMERCIAL` wired into role_hierarchy

**Severity**: Major → **RESOLVED** (commit 2026-05-04)
**Decision**: Option B — wire role into hierarchy.

**Change applied** to `config/packages/security.yaml`:

```yaml
ROLE_COMMERCIAL: [ROLE_INTERVENANT, ROLE_USER]
ROLE_MANAGER: [ROLE_CHEF_PROJET, ROLE_COMMERCIAL, ROLE_INTERVENANT, ROLE_USER]
ROLE_ADMIN: [ROLE_MANAGER, ROLE_CHEF_PROJET, ROLE_COMMERCIAL, ROLE_INTERVENANT, ROLE_USER]
```

`ROLE_COMMERCIAL` is now a peer of `ROLE_CHEF_PROJET`, transitively inherited by `ROLE_MANAGER`, `ROLE_COMPTA`, `ROLE_ADMIN`, `ROLE_SUPERADMIN`.

**Original state** (kept for traceability):

**Spec claim**: PRD FR-IAM-04 hierarchy of 7 roles.

**Code reality**:
```bash
grep -lE "ROLE_COMMERCIAL" -r src
src/Command/CreateTestDataCommand.php   ← only reference
```

`ROLE_COMMERCIAL` is **referenced solely in a fixture command**, not in `security.yaml` `role_hierarchy`. If the fixture creates a user with this role, the user effectively has **only `ROLE_USER`** (Symfony default for unknown roles).

**Recommendation**:
- **Option A** (clean-up): remove `ROLE_COMMERCIAL` from `CreateTestDataCommand.php`; replace with `ROLE_MANAGER` or another existing role.
- **Option B** (introduce role): add `ROLE_COMMERCIAL: [ROLE_USER]` in `role_hierarchy` and update PRD §4 personas + this report.

**Effort**: S (<2h).

---

### GAP-C4 — `src/ApiResource/` directory empty but API Platform active

**Severity**: Minor (documentation/architecture clarity)

**Spec claim**: scan-report §3 noted `src/ApiResource/` directory exists.

**Code reality**: directory contains 0 PHP files, but at least **10 entities expose `#[ApiResource]`** (Order, Project, Invoice, Timesheet, Contributor, User, InvoiceLine, RunningTimer, FactProjectMetrics, FactStaffingMetrics). API Platform mapping happens via attributes on Doctrine entities — common pattern but couples Domain and API layers.

**Recommendation**:
1. Document the chosen API Platform pattern (attributes-on-entities vs decoupled DTOs/Providers/Processors).
2. Consider migrating new BCs (`Vacation`) towards decoupled `App\ApiResource\*` DTOs + State Providers/Processors in line with DDD layering.
3. Update `prd.md` §6 NFR-DX-05 to mention pattern.

**Effort**: S (doc) + L (refactor if desired).

---

### GAP-C5 ✅ RESOLVED — DDD BC stubs cleaned up

**Severity**: Major → **RESOLVED** (commit 2026-05-04)
**Decision**: cleanup all stub directories.

**Removed directories** (empty, scaffolding-only):
- `src/Domain/Catalog/`, `src/Domain/Notification/`, `src/Domain/Reservation/`
- `src/Application/Catalog/`, `src/Application/Client/`, `src/Application/Notification/`, `src/Application/Reservation/`

**Remaining DDD BCs**:
- `src/Domain/Vacation/` (real CQRS reference implementation)
- `src/Application/Vacation/`

**Notes**:
- 0 PHP files were impacted (all dirs were empty).
- Future DDD BC migrations will recreate dirs as needed (e.g. `Order`, `Project`, `Invoice`).
- `Reservation` is **not** a HotOnes concept; permanent removal.

**Effort**: S (done).

---

### GAP-C6 — Two architectures coexist (legacy `Entity/Controller/Repository` vs DDD)

**Severity**: Major

**Spec claim**: `.claude/rules/02-architecture-clean-ddd.md` mandates Domain/Application/Infrastructure/Presentation layers.

**Code reality**: only `Vacation` BC respects the rule. 63 entities in `src/Entity` carry Doctrine attributes (forbidden in Domain per rules). Most controllers in `src/Controller` (not `src/Presentation/Controller`).

**Recommendation**:
1. Explicit migration roadmap per BC (e.g. Order → DDD before Q3, then Project, then Invoice).
2. Track via Deptrac rules (forbid new code under `src/Entity`).
3. Keep PRD §5 / §6 NFR-QLT-06 honest about current coverage (legacy = ~95%).

**Effort**: L (multi-quarter).

---

### GAP-C7 — Dual asset pipeline (AssetMapper + Webpack Encore)

**Severity**: Minor

**Spec claim**: `composer.json` lists both `symfony/asset-mapper` and `symfony/webpack-encore-bundle`. Two `build-assets.sh` scripts (local + docker).

**Code reality**: both active. README references both, no migration documented.

**Recommendation**:
- Decide target pipeline. AssetMapper is Symfony's recommended path for Symfony 7+/8.
- Consolidate build scripts.

**Effort**: M.

---

### GAP-C8 — PHP 8.5 minimum tightness

**Severity**: Minor

**Spec claim**: `composer.json` `require.php >=8.5`.

**Code reality**: PHP 8.5 is the very latest release (assistant knowledge cutoff Jan 2026); some hosting providers and CI runners may not yet ship 8.5.

**Recommendation**: confirm CI matrix runs against PHP 8.5; document rationale or relax to `^8.4|^8.5`.

**Effort**: S.

---

## D. Test Gaps (Untested or Under-tested)

### D.1 Suite-level overview

| Suite | Test files | Notes |
|-------|-----------:|-------|
| Unit | 47 | Backbone of safety net |
| Integration | 21 | Repositories + integrations |
| Functional | 16 | HTTP / Controllers |
| Security | 4 | OK for tests/Security suite |
| Contract | 2 | API contract |
| Api | 1 | API surface |
| **Total** | **91** (+11 in Support/Foundry) | for 446 src files |

> Test/source ratio ~ **20%** (91 / 446). Target per `.claude/rules/07-testing.md` is ≥80% line coverage; cannot be inferred without coverage report.

### D.2 Gaps highlighted by current sprint

- **Sprint-005 goal**: "Stabiliser la suite de tests fonctionnels (Vacation + connectors), eliminer la dette mockObjects et fiabiliser le pre-push hook pour ne plus recourir a `--no-verify`" → existing test debt acknowledged.

### D.3 High-value missing tests (inferred from criticality vs visible coverage)

| GAP | Area | Severity | Reasoning |
|-----|------|----------|-----------|
| GAP-D1 | **Multi-tenant cross-isolation regression test** (Alice tenant A reads → must not see tenant B) | Critical | C1 cannot be fixed without it. Currently no test in `tests/Security` matches this surface (only 4 files). |
| GAP-D2 | **Voter tests** for resource ownership | Major | C2 — only `CompanyVoter` exists; no entity voter ⇒ no entity voter test. |
| GAP-D3 | **Quote/Order state machine** transitions (legal vs illegal paths) | Major | `OrderStatus` enum has 7 states but no centralised guard like `VacationStatus` provides. |
| GAP-D4 | **Notification dispatch matrix** (all 11 `NotificationType` × channels × preferences) | Major | Surface is large, no test pivot detected. |
| GAP-D5 | **Margin computation formula** | Major | Core domain logic; formula not located in code → tests can't be cited. |
| GAP-D6 | **Project budget alert / low margin alert thresholds** | Major | Threshold seuil source unknown; tests pas localisés. |
| GAP-D7 | **HubSpot / BoondManager integration retry & idempotency** | Major | `Service/{HubSpot,BoondManager}/*` external; tests `Integration/` partial. |
| GAP-D8 | **AI chatbot prompt-injection / scope leakage** | Major | LLM-driven, multi-tenant — needs guardrail tests (no cross-tenant data via prompt). |
| GAP-D9 | **API contract drift** (`/api/**`) | Minor | Only 2 contract tests vs ~50+ endpoints. |
| GAP-D10 | **GDPR account deletion / cookie consent flow** | Minor | RGPD compliance regression risk. |

---

## E. Severity summary

```
Critical: GAP-C1 (multi-tenant filter), GAP-C2 (voters), GAP-D1 (cross-tenant regression test)
Major:    GAP-B2, GAP-B3, GAP-B8, GAP-B17, GAP-B18,
          GAP-C3 (ROLE_COMMERCIAL), GAP-C5, GAP-C6,
          GAP-D2..D8
Minor:    GAP-A1, GAP-A2, GAP-B1, GAP-B4, GAP-B5, GAP-B11, GAP-B14,
          GAP-B15, GAP-B16, GAP-B19, GAP-B21,
          GAP-C4, GAP-C7, GAP-C8,
          GAP-D9, GAP-D10
Info:     GAP-B6, GAP-B7, GAP-B9, GAP-B10, GAP-B12, GAP-B13
```

| Severity | v1 | v2 (post atelier) |
|----------|---:|------------------:|
| Critical | 3 | 3 (R-01/C1, R-02/C2, D1) |
| Major | 14 | 12 (-GAP-C3, -GAP-C5 résolus) |
| Minor | 17 | 17 |
| Info | 6 | 6 |
| **Total raw items** | **40** | **38** |
| ✅ RESOLVED | 0 | 2 (GAP-C3, GAP-C5) |

> Headline `21 gaps` at top counts **distinct themes** (some IDs cover several findings, e.g. B18+B20 = star-schema theme).

---

## F. Remediation action list (priority-ranked)

| # | Action | Severity | Effort | Owner |
|---|--------|----------|--------|-------|
| 1 | Implement `TenantContext` + `TenantFilter` SQLFilter + `TenantAwareTrait`; backfill on tenant-scoped entities | 🔴 Critical | L | tech lead + 1 dev (2-3 sprints) |
| 2 | Add cross-tenant isolation regression tests (Alice/Bob across all top BCs) | 🔴 Critical | M | QA + 1 dev |
| 3 | Implement entity voters for `Project`, `Order`, `Invoice`, `Timesheet`, `Vacation`, `Client`, `ExpenseReport`, `Contributor` | 🔴 Critical | L | tech lead |
| 4 | Cleanup `ROLE_COMMERCIAL` (Option A) or wire it into hierarchy (Option B) | Major | S | any dev |
| 5 | Delete `src/Domain/Reservation` + `src/Application/Reservation` if confirmed dead code | Major | S | tech lead |
| 6 | Decide fate of `src/Domain/Catalog` & `src/Domain/Notification` stubs (flesh out or delete) | Major | M | tech lead |
| 7 | Document API Platform pattern (attributes-on-entities) and decide DDD migration target | Major | S (doc) | tech lead |
| 8 | Add FR/US for `BusinessUnit`, `Company` admin, `CompanySettings`, Analytics warehouse (FactProjectMetrics/FactStaffingMetrics + Dim*) | Major | M | PO |
| 9 | Add FR/US for Project Tasks API (`TaskApiController`) | Major | S | PO |
| 10 | Document margin formula + alert thresholds; add tests | Major | M | tech lead + PO |
| 11 | Notification dispatch matrix tests (11 types × channels × preferences) | Major | M | dev |
| 12 | HubSpot / BoondManager retry + idempotency tests | Major | M | dev |
| 13 | AI chatbot scope-leakage / cross-tenant prompt-injection tests | Major | M | dev + security |
| 14 | Quote/Order state-machine guard (similar to `VacationStatus`) + tests | Major | M | dev |
| 15 | DDD migration roadmap per BC (Order → Project → Invoice) | Major | L | tech lead |
| 16 | Resolve Provider/Vendor lexicon + missing FRs | Minor | S | PO |
| 17 | Audit & document 46 console commands; add to PRD §X Operations CLI | Minor | M | tech lead |
| 18 | Decide AssetMapper vs Webpack Encore; consolidate build | Minor | M | front lead |
| 19 | Confirm PHP 8.5 in CI; document rationale or relax to `^8.4|^8.5` | Minor | S | devops |
| 20 | Fill PRD §3 Goals & Metrics + §10 Glossary | Minor | M | PO |
| 21 | API contract tests expansion | Minor | M | dev |
| 22 | Backoffice / dashboard / staffing controllers full mapping in PRD | Minor | S | PO |
| 23 | RGPD flow regression tests (deletion + consent) | Minor | S | dev |

> **Sprint hint**: items #1, #2, #3 form the next "Security Hardening" epic and should precede any new tenant onboarding in production.

Effort legend: S = <2h · M = 2-8h · L = >8h.

---

## G. Mapped onto current sprint cadence

| Sprint | Capacity (pts) | Suggested focus |
|--------|---------------|-----------------|
| sprint-005 (in flight, 32 pts) | Test stabilization, mockObjects debt, pre-push hook (sprint goal already set) — **leave intact** | |
| sprint-006 (planned) | **Security Hardening epic**: items #1, #2, #3 (multi-tenant filter + voters + cross-tenant regression). Estimate ~32 pts. |
| sprint-007 | DDD cleanup + decisions: items #4, #5, #6, #14 |
| sprint-008 | Documentation closure: items #8, #9, #16, #17, #20, #22 |
| sprint-009+ | Test coverage push: items #10–#13, #21, #23 |

---

## H. Next commands

1. **PO**: validate this report; mark each gap as **Accept / Reject / Defer**.
2. `/project:add-epic "Security Hardening"` — to track items #1..#3.
3. `/project:add-story` per accepted Major item.
4. `/project:dependencies` — to compute critical path.
5. Re-run `/project:scan` + `/project:gap-analysis` after each remediation milestone to track convergence.

---

## I. Confidence

| Section | Confidence | Notes |
|---------|------------|-------|
| §A Spec without code | HIGH | PRD inferred from code, so almost empty by construction. |
| §B Code without spec | HIGH | Direct enumeration of controllers/entities. |
| §C Mismatches | HIGH for C1-C5 (verified by grep), MEDIUM for C6-C8 (architectural judgement). |
| §D Test gaps | MEDIUM | File counts only — no coverage report consumed. |
| §F Remediation effort estimates | LOW-MEDIUM | Heuristic; refine in planning poker. |

---

> **Bottom line**: HotOnes has a rich functional surface (~80 controllers, ~63 entities, ~338 routes), but three structural risks dominate — **multi-tenant isolation not enforced at the ORM layer**, **voter coverage thin**, and **DDD migration partial**. Address those before further commercial scale-up.
