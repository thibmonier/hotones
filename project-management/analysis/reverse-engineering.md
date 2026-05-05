# Reverse-Engineering Analysis Notes

> Companion to `project-management/prd.md`. Documents inference rationale, confidence levels and open questions.
>
> Generated: 2026-05-04 by `/project:reverse-prd`.
> Source artefacts: `project-management/scan-report.md`, `README.md`, `composer.json`, `config/packages/*`, `src/**`, `.bmad/sprint-status.yaml`.

---

## 1. Confidence map per PRD section

| PRD section | Confidence | Why |
|-------------|------------|-----|
| §1 Executive Summary | HIGH | README + composer + scan triangulate the same picture |
| §2 Problem Statement | MEDIUM | Domain pain (margin tracking, fragmented ops) inferred from notification types + dashboards. Not validated with users. |
| §3 Goals & Metrics | LOW (placeholder) | Code carries no OKR signal. Pure PO input required. |
| §4 Personas | MEDIUM-HIGH | Role hierarchy + `app:create-test-users` give 6 explicit personas. Archetypes/demographics still TBD. |
| §5 Functional Requirements | HIGH | Direct mapping from controllers, entities, enums, events, CQRS handlers. |
| §6 Non-Functional Requirements | MEDIUM-HIGH | Stack components imply NFRs; targets (<200 ms, etc.) come from `.claude/rules`, not measured. |
| §7 Scope & Constraints | HIGH | Stack lock-ins are factual; "out of scope" is by absence of dependency. |
| §8 Risks | MEDIUM | Risks derived from gaps observed during scan (R-01..R-12). |
| §10 Glossary | LOW | Skeleton only; ubiquitous language not formalised in code. |

---

## 2. Inference rules applied

### 2.1 Persona inference

| Signal | Persona |
|--------|---------|
| `ROLE_INTERVENANT` in role hierarchy + `intervenant@test.com` test user | P-001 Intervenant |
| `ROLE_CHEF_PROJET` + `chef-projet@test.com` | P-002 Chef de projet |
| `ROLE_MANAGER` + `manager@test.com` | P-003 Manager |
| `ROLE_COMPTA` + `compta@test.com` | P-004 Comptabilité |
| `ROLE_ADMIN` + `admin@test.com` | P-005 Admin (tenant) |
| `ROLE_SUPERADMIN` + `superadmin@test.com` | P-006 Superadmin (platform) |
| `PUBLIC_ACCESS` rules + `LeadCapture` + blog | P-007 Visitor |
| API JWT + `lexik/jwt-authentication-bundle` | P-008 External integration |
| `ROLE_COMMERCIAL` referenced but not in hierarchy | P-009 ⚠️ orphan, decision needed |

### 2.2 Functional requirement inference

| Code pattern | Mapped to |
|--------------|-----------|
| Controller class + `#[Route]` | One or more functional requirements per route group |
| Doctrine entity | Likely CRUD requirement; statuses → state-machine requirement |
| Enum implementing status | Lifecycle requirement (e.g. `OrderStatus`) |
| `src/Event/*Event.php` | Business event → notification requirement (cross-checked with `NotificationType`) |
| CQRS Command/Handler under `src/Application/*/Command/` | Use-case requirement |
| CQRS Query/Handler under `src/Application/*/Query/` | Read-side requirement |
| Service class under `src/Service/{HubSpot,BoondManager,AI,Order,Planning,Timesheet}` | Integration / capability requirement |
| Console command under `src/Command` | Operations / batch requirement (not surfaced individually in PRD; see Ops backlog) |
| Public path in `access_control` | Marketing / lead requirement |

### 2.3 NFR inference

| Dependency / config | NFR derived |
|---------------------|-------------|
| symfony/security-bundle + scheb/2fa | Auth + 2FA requirements |
| nelmio/security-bundle | CSP requirement |
| symfony/rate-limiter | Rate limiting NFR |
| symfony/messenger + Redis transport | Async + reliability NFR |
| sentry/sentry-symfony | Observability NFR |
| KnpPaginatorBundle | Pagination NFR |
| liip/imagine-bundle | Image pipeline NFR |
| flysystem-aws-s3 | External storage NFR |
| docker-compose with mariadb/redis/nginx | Containerisation NFR |
| .github/workflows × 9 | CI/CD NFR (lint, tests, security, contracts, smoke, backup) |
| SonarCloud / Snyk badges | Continuous quality + SCA NFRs |
| `.claude/rules/12-performance.md` | Performance targets (<200 ms / <100 ms) |
| `.claude/rules/02-architecture-clean-ddd.md` | Architecture constraint |

---

## 3. Open questions for the PO / tech lead

### 3.1 Domain
1. Is **Reservation** an actual HotOnes BC or a copy-paste template from another project? (`Application/Reservation`, `Domain/Reservation` only contain Event scaffolds.)
2. Same for **Catalog** and **Notification** in `Domain/` — only Event files present, no entities/value objects/repositories.
3. Where is the canonical **margin formula** (revenue × TJM − cost × CJM − purchases)? Not located in this scan.
4. What is the **billing cadence**: per milestone (`BillingMarker`), per `OrderPaymentSchedule`, or both?
5. What is the **tenant onboarding flow** for a brand-new agency signing up (vs `app:create-test-users`)?

### 3.2 Personas & roles
6. Is **`ROLE_COMMERCIAL`** dead code or a missing role to be wired? Where is it referenced and with what intent?
7. Are **Intervenant** and **Chef de projet** distinct user populations or the same person operating in two modes?
8. Does **Superadmin** correspond to platform staff (HotOnes employees) or to a tenant super-user?

### 3.3 Multi-tenancy
9. Where is the **tenant filter** enforced? Doctrine SQLFilter? Voter? Repository? README claims "isolation complète" but the scan did not locate the mechanism.
10. Is there a **TenantContext** service equivalent to `.claude/rules/14-multitenant.md`?
11. What happens for **shared entities** like `Skill`, `Technology`, `BlogPost`, `SaasProvider` — global (platform) or per-tenant?

### 3.4 Business priorities
12. What is the **MVP perimeter** today and what is being added next?
13. What is the **target ICP** (agency size, revenue, geography)?
14. What competing tools are typically displaced (BoondManager? Toggl? Jira? Excel?)
15. Pricing model: **per seat**, **per company**, **freemium**?

### 3.5 Technical
16. Is **PHP 8.5** mandatory, or is 8.4 acceptable for compatibility with hosted runners?
17. Why both **AssetMapper and Webpack Encore**? Migration in progress?
18. What is the **rollout plan for DDD migration** of legacy `Entity/Controller` modules?
19. **Rector / Deptrac** configuration — currently active in CI?

---

## 4. Source artefacts traced

| Artefact | Used for |
|----------|----------|
| `README.md` | Stack, quickstart, test users, dashboards |
| `composer.json` | Stack depth, AI providers, security pkgs, integrations |
| `config/packages/security.yaml` | Role hierarchy, access_control |
| `config/packages/messenger.yaml` | Async transports |
| `config/packages/cache.yaml` | Cache layer |
| `src/Enum/OrderStatus.php` | Quote/order lifecycle |
| `src/Enum/NotificationType.php` | 11 notification kinds → §5.11 + cross-cutting reqs |
| `src/Enum/NotificationChannel.php` | Multi-channel dispatch |
| `src/Event/*.php` | 8 business events (margin, budget, payment, KPI, overload, timesheet, quote) |
| `src/Domain/Vacation/**` | Real CQRS BC with state machine, used as reference pattern |
| `src/Application/Vacation/**` | 4 commands + 3 queries + DTO + notification message |
| `src/Controller/**` | 80 controllers → routes → functional surface |
| `src/Entity/*.php` | 63 entities → modules grouping |
| `src/Service/{HubSpot,BoondManager,AI,Order,Planning,Timesheet}` | Integration + capability inference |
| `src/Command/*.php` | 46 console commands → operations posture |
| `.bmad/sprint-status.yaml` | Sprint cadence + current sprint goal (test-debt) |
| `.github/workflows/*.yml` | CI/CD posture |
| `.snyk` + `roave/security-advisories` | SCA posture |
| `.claude/rules/*.md` | Project standards (DDD, security, multi-tenant, performance) |

---

## 5. Items NOT inferable from code (require human input)

- Business OKRs / quarterly goals.
- ARR, MRR, churn, NRR or any commercial KPI.
- Pricing model and tier definition.
- ICP definition.
- Competitive landscape.
- Marketing positioning.
- Brand guidelines.
- Roadmap commitments / dates.
- Customer support SLAs.

---

## 6. Recommended next actions

1. PO + tech lead session to validate `prd.md` §3 (Goals), §4 (Personas), §10 (Glossary) and answer §3 of this analysis.
2. Decide fate of `Domain/{Catalog,Notification,Reservation}` and `Application/Reservation` (real BCs vs cleanup).
3. Decide fate of `ROLE_COMMERCIAL` (wire vs remove).
4. Audit voter coverage; produce a remediation plan (R-01).
5. Verify multi-tenant filter implementation (R-03) — possibly add a regression test that tries cross-tenant access.
6. Run `/project:reverse-stories` to materialize user stories from §5.
7. Run `/project:gap-analysis` to surface code-without-spec items.
