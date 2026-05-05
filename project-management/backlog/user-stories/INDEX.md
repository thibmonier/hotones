# User Stories Index — HotOnes (DRAFT)

> Reverse-engineered from codebase via `/project:reverse-stories`.
> Generated: 2026-05-04. Source: `project-management/scan-report.md` + `project-management/prd.md`.
> Status: **DRAFT** — every story marked `INFERRED`. Human validation required before sprint planning.

---

## Headlines

| Metric | Value |
|--------|-------|
| Total stories | **85** |
| Total story points (raw estimate) | **385** |
| Modules (capability groups) | **15** |
| FRs covered | **85 / 85** (1:1 mapping with PRD §5) |
| Stories without matching FR (code-without-spec) | **0** detected at this granularity (use `/project:gap-analysis` for finer view) |

> **Pragmatic deviation from spec**: command spec describes one file per story (`US-XXX-{slug}.md`). Given 85 stories, output is consolidated into **one file per module** under `project-management/backlog/user-stories/{MODULE}.md`, each containing full Card / 3C / Gherkin / INVEST / Estimation per story. Split into individual files later if BMAD tooling requires it.

---

## MoSCoW distribution

| Priority | Count | % |
|----------|------:|--:|
| Must | 45 | 53% |
| Should | 33 | 39% |
| Could | 7 | 8% |
| Won't (this scope) | 0 | 0% |
| **Total** | **85** | **100%** |

---

## Module map

| Module file | Theme | Stories | Pts | FR range |
|-------------|-------|--------:|----:|----------|
| [IAM.md](IAM.md) | Identity, Access & Tenancy | 9 | 37 | FR-IAM-01..09 |
| [CRM.md](CRM.md) | CRM & Sales Pipeline | 5 | 26 | FR-CRM-01..05 |
| [ORD.md](ORD.md) | Quotes & Orders | 5 | 29 | FR-ORD-01..05 |
| [PRJ.md](PRJ.md) | Project Delivery | 9 | 43 | FR-PRJ-01..09 |
| [PLN.md](PLN.md) | Planning, Staffing & Workload | 5 | 29 | FR-PLN-01..05 |
| [TIM.md](TIM.md) | Time Tracking | 4 | 16 | FR-TIM-01..04 |
| [VAC.md](VAC.md) | Vacations (DDD CQRS BC) | 8 | 22 | FR-VAC-01..08 |
| [INV.md](INV.md) | Invoicing & Treasury | 6 | 26 | FR-INV-01..06 |
| [HR.md](HR.md) | HR & Performance | 8 | 36 | FR-HR-01..08 |
| [AN.md](AN.md) | Analytics, Forecasting & AI | 5 | 34 | FR-AN-01..05 |
| [NTF.md](NTF.md) | Notifications | 4 | 18 | FR-NTF-01..04 |
| [SAAS.md](SAAS.md) | SaaS Catalogue | 3 | 10 | FR-SAAS-01..03 |
| [INT.md](INT.md) | External Integrations | 3 | 19 | FR-INT-01..03 |
| [MKT.md](MKT.md) | Content & Marketing | 4 | 17 | FR-MKT-01..04 |
| [OPS.md](OPS.md) | Operations & Platform | 7 | 23 | FR-OPS-01..07 |
| **Total** | | **85** | **385** | |

---

## Suggested MVP slice (subject to PO validation)

> Pragmatic MVP = "minimum to operate one tenant agency end-to-end with margin tracking". Excludes gamification, SaaS catalogue, NPS, AI chatbot.

### Inner core — Walking-skeleton-aligned

| Module | Stories Must | Pts |
|--------|------|-----|
| IAM | US-001, US-002, US-004, US-005, US-006, US-007, US-008, US-009 | 32 |
| CRM | US-010, US-011 | 8 |
| ORD | US-015, US-016, US-018, US-019 | 24 |
| PRJ | US-020, US-025, US-026 | 18 |
| PLN | US-029, US-032, US-033 | 16 |
| TIM | US-034, US-036, US-037 | 11 |
| VAC | US-038..045 (whole BC, ref pattern) | 22 |
| INV | US-046, US-047, US-051 | 13 |
| AN | US-060, US-061 | 13 |
| NTF | US-065..068 | 18 |
| INT | US-074 (S3 only) | 3 |
| OPS | US-079, US-081, US-082 | 12 |
| **MVP total** | **~45 stories** | **~190 pts** |

> At sprint-005 velocity (32 pts), MVP ≈ **6 sprints** if greenfield. Since most is already coded, sprint focus is on **gap closure + alignment** rather than green-build.

---

## Traceability matrix (FR ↔ US)

| FR | US | Module |
|----|----|--------|
| FR-IAM-01 | US-001 | IAM |
| FR-IAM-02 | US-002 | IAM |
| FR-IAM-03 | US-003 | IAM |
| FR-IAM-04 | US-004 | IAM |
| FR-IAM-05 | US-005 | IAM |
| FR-IAM-06 | US-006 | IAM |
| FR-IAM-07 | US-007 | IAM |
| FR-IAM-08 | US-008 | IAM |
| FR-IAM-09 | US-009 | IAM |
| FR-CRM-01..05 | US-010..014 | CRM |
| FR-ORD-01..05 | US-015..019 | ORD |
| FR-PRJ-01..09 | US-020..028 | PRJ |
| FR-PLN-01..05 | US-029..033 | PLN |
| FR-TIM-01..04 | US-034..037 | TIM |
| FR-VAC-01..08 | US-038..045 | VAC |
| FR-INV-01..06 | US-046..051 | INV |
| FR-HR-01..08 | US-052..059 | HR |
| FR-AN-01..05 | US-060..064 | AN |
| FR-NTF-01..04 | US-065..068 | NTF |
| FR-SAAS-01..03 | US-069..071 | SAAS |
| FR-INT-01..03 | US-072..074 | INT |
| FR-MKT-01..04 | US-075..078 | MKT |
| FR-OPS-01..07 | US-079..085 | OPS |

---

## Known overlaps to resolve (PO arbitration)

| Stories | Issue | Recommendation |
|---------|-------|----------------|
| US-013 (FR-CRM-04 HubSpot sync) ↔ US-072 (FR-INT-01 HubSpot integration) | Two FRs covering HubSpot under different lenses (CRM-side feature vs platform integration) | Keep both: US-013 = CRM domain workflow; US-072 = settings + connector engineering. Cross-link in dependencies. |
| US-012 (FR-CRM-03 lead capture) ↔ US-077 (FR-MKT-03 lead-magnet) | Lead magnet is the marketing surface; CRM lead pipeline is the receiving end. | Keep both, link as parent (CRM)/child (MKT) or vice-versa per chosen flow. |
| US-014 (FR-CRM-05 sales dashboard) ↔ US-061 (FR-AN-02 dashboards) | Sales dashboard is one of the multi-role dashboards. | US-061 is parent capability; US-014 is concrete instance. |

---

## Validation checklist

For PO + tech lead before promoting to backlog:

- [ ] Persona assignment validated per story (especially `ROLE_COMMERCIAL` orphan).
- [ ] MoSCoW priorities reviewed against business goals (PRD §3 still PLACEHOLDER).
- [ ] MVP slice confirmed or adjusted.
- [ ] Overlaps in §5 resolved.
- [ ] DDD CQRS Vacation BC (US-038..045) confirmed as reference pattern for upcoming BC migrations.
- [ ] Risks R-01 (voters), R-02 (ROLE_COMMERCIAL), R-03 (multi-tenant filter) explicitly assigned to stories or dedicated tech-debt items.
- [ ] Estimates challenged in planning poker (current values are heuristic).
- [ ] FR mapping complete (1:1 — verified above).

---

## Confidence

| Area | Confidence | Notes |
|------|------------|-------|
| FR↔US mapping | HIGH | Generated 1:1 from PRD §5 |
| Persona assignment | MEDIUM-HIGH | Derived from `security.yaml` + controller paths |
| Acceptance criteria | MEDIUM | Inferred from code behavior + entity invariants; **not extracted from existing tests** in this pass (would require deeper code reading per controller) |
| Estimation | LOW | Heuristic by complexity; replace with planning poker outcome |
| MoSCoW | LOW-MEDIUM | Educated guess; PO decision required |
| Overlaps & dependencies | MEDIUM | Surfaced visible cases; full dependency graph needs `/project:dependencies` run |

---

## Next steps

1. PO + Tech lead session: validate §"Validation checklist" above.
2. Run `/project:gap-analysis` to surface code without matching FR/US (this scan emitted 1:1 mapping at the FR level; controllers and routes likely produce additional uncovered behaviour at finer granularity).
3. Run `/project:dependencies` to produce the dependency graph between stories.
4. Run `/project:gate-validate-backlog` against INVEST criteria.
5. Sync into BMAD format: split per-story files into `project-management/backlog/user-stories/US-XXX-{slug}.md` once estimates and priorities are locked.

---

## File layout produced

```
project-management/
├── scan-report.md
├── prd.md
├── analysis/
│   └── reverse-engineering.md
└── backlog/
    └── user-stories/
        ├── INDEX.md           ← this file
        ├── IAM.md             (US-001..009)
        ├── CRM.md             (US-010..014)
        ├── ORD.md             (US-015..019)
        ├── PRJ.md             (US-020..028)
        ├── PLN.md             (US-029..033)
        ├── TIM.md             (US-034..037)
        ├── VAC.md             (US-038..045)
        ├── INV.md             (US-046..051)
        ├── HR.md              (US-052..059)
        ├── AN.md              (US-060..064)
        ├── NTF.md             (US-065..068)
        ├── SAAS.md            (US-069..071)
        ├── INT.md             (US-072..074)
        ├── MKT.md             (US-075..078)
        └── OPS.md             (US-079..085)
```
