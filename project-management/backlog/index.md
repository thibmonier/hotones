# Backlog Index

> Dernière mise à jour: 2026-05-16

---

## Résumé Global

| Type | 🔴 To Do | 🟡 In Progress | ⏸️ Blocked | 🟢 Done | Total |
|------|----------|----------------|------------|---------|-------|
| EPICs | 0 | 1 | 0 | 2 | 3 |
| User Stories | ~70 | 0 | 0 | ~44 | ~114 |
| Tasks (in sprints) | — | — | — | — | 100+ |

> Statuts US dérivés livraisons git sprint-001..026. Delta depuis 2026-05-11 :
> +15 US livrées (US-110..US-119 EPIC-003 Phase 4+5 + 5 dette/tests/ops),
> +2 OPS captures backlog (OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK +
> OPS-DEPENDENCY-FRESHNESS-CHECK).

---

## EPICs

| ID | Nom | Statut | Priorité | US | Progression |
|----|-----|--------|----------|-----|-------------|
| EPIC-001 | Migration Clean Architecture DDD | 🟢 Done | High | ~15 (sprint-007..014) | 100 % (4 phases livrées) |
| EPIC-002 | Observability & Performance | 🟢 Done | High | US-091..US-095 | 100 % (sprints 016-018, 124-130 % engagement) |
| EPIC-003 | WorkItem & Profitability | 🟡 Phase 5 done | High | US-097..US-119 (Phase 6 TBD) | 100 % Phase 5 (8 sp livrés, 9 KPIs dashboard) |

---

## Sprint Actuel

**sprint-027 — Ops migration prod + dette résorption + features TBD — kickoff pending**

| Champ | Valeur |
|---|---|
| Période | 2026-07-08 → 2026-07-22 (10 jours ouvrés) — clôture anticipée probable |
| Capacité | 12 pts ferme (7ᵉ confirmation recalibrage durable visée) |
| Goal | Solde T-113-07 dry-run prod + cleanup Mago assertion-style + 2 OPS stories process + features TBD Planning P1 |
| Stories engagées (6 pts ferme) | T-113-07 + MAGO-LINT-BATCH-003 + OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK + OPS-DEPENDENCY-FRESHNESS-CHECK + KPI-TEST-SUPPORT-TRAIT |
| Stories TBD (6 pts) | Phase 6 EPIC-003 OU autre — Planning P1 |
| Statut | kickoff_pending — atelier OPS-PREP-J0 J-2 + Sprint Planning P1 |

Voir : `project-management/sprints/sprint-027/sprint-goal.md`

### Sprint Précédent — sprint-026 ✅ CLOSED 2026-05-16

| Champ | Valeur |
|---|---|
| Engagement | 12 pts ferme |
| Livré | **11 pts (92 %)** — T-113-07 reporté ops humaine |
| Stories | US-117 + US-119 + MAGO-LINT-BATCH-002 + COVERAGE-014 + TEST-FUNCTIONAL-FIXES-003 |
| Highlights | EPIC-003 Phase 5 complète (9 KPIs total dashboard), pattern KpiCalculator 7ᵉ application, nouveau Domain Event ProjectMarginRecalculatedEvent, sur-livraison TEST-FUNCTIONAL-FIXES-003 (14 markers retirés vs 6 spec), 6ᵉ sprint consécutif 0 commit `--no-verify` |

Voir : `project-management/sprints/sprint-026/sprint-review.md` + `sprint-retro.md`

### Sprint Avant-Précédent — sprint-025 ✅ CLOSED 2026-05-15

| Champ | Valeur |
|---|---|
| Engagement | 12 pts ferme |
| Livré | **12 pts (100 %)** |
| Stories | US-114 + US-115 + US-116 + MAGO-LINT-BATCH-001 + VACATION-REPO-AUDIT + TEST-COVERAGE-013 |
| Highlights | EPIC-003 Phase 5 démarrée (2 KPIs Revenue forecast + Conversion rate + drill-down DSO/lead-time), Sub-epic D dette soldée, Mago baseline activé (1307 issues) |

Voir : `project-management/sprints/sprint-025/sprint-review.md`

---

## Sprints Historiques

| Sprint | Nom | Pts planifiés | Pts livrés | Vélocité |
|---|---|---:|---:|---:|
| sprint-001 | Walking Skeleton | 21 | 21 | 21 |
| sprint-002 | Tests Consolidation | 34 | 34 | 34 |
| sprint-003 | Stabilization | 30 | 30 | 30 |
| sprint-004 | Quality Foundation | 30 | 30 | 30 |
| sprint-005 | Test Stabilization & Tech-Debt | 26 | 26 | 26 |
| sprint-006 | Test Debt Cleanup & Workflow Hygiene | 22 | 19 | 19 |
| sprint-007 | Security Hardening + DDD Foundation | 32 | 32 | 32 |
| sprint-008 | DDD Phase 1 + Tech Debt + PRD/DB | 17 | 17 | 17 |
| sprint-009 | DDD Phase 2 Strangler Fig + Critical Fixes | 15 | 14 | 14 |
| sprint-010 | DDD Phase 2 Completion | TBD | TBD | TBD |
| sprint-011 | DDD Phase 3 Completion + E2E Tests | 17-22 | TBD | TBD |
| sprint-012..018 | DDD Phase 4 + Buffer + EPIC-002 | — | — | — |
| sprint-019 | EPIC-003 scoping + Phase 1 | 12 | TBD | TBD |
| sprint-020 | EPIC-003 Phase 2 ACL | TBD | TBD | TBD |
| sprint-021 | EPIC-003 Phase 3 RecordWorkItem | TBD | TBD | TBD |
| sprint-022 | EPIC-003 Phase 3 Completion | 12 | 13 (108 %) | 13 |
| sprint-023 | EPIC-003 Phase 3 Finition ✅ closed 2026-05-11 | 12 | 12 (100 %) | 12 |
| sprint-024 | EPIC-003 Phase 4 KPIs DSO/lead-time/adoption + migration ✅ closed 2026-05-14 | 12 | 12 (100 %) | 12 |
| sprint-025 | EPIC-003 Phase 5.1 Revenue forecast + Conversion + drill-down ✅ closed 2026-05-15 | 12 | 12 (100 %) | 12 |
| sprint-026 | EPIC-003 Phase 5.2 + dette + T-113-07 reporté ✅ closed 2026-05-16 | 12 | 11 (92 %) | 11 |
| sprint-027 | Ops migration prod + dette + Phase 6 TBD (kickoff pending) | 12 | — | — |

> Détail vélocité : `.bmad/sprint-status.yaml` (rebuild en cours, voir
> backup `.bmad/sprint-status.yaml.backup-2026-05-11-sprint011`).

---

## Structure backlog

```
backlog/
├── index.md                          # Ce fichier
├── epics/
│   ├── EPIC-001-migration-clean-architecture-ddd.md  ✅
│   ├── EPIC-002-observability-and-performance.md     ✅
│   └── EPIC-003-workitem-and-profitability.md        🟡 Phase 5 done
├── user-stories/
│   ├── US-001-*.md ... US-142-*.md   # 100 fichiers individuels (split 2026-05-11)
│   ├── INDEX.md                       # Index legacy (pré-split)
│   └── by-bc-archive/                 # BC files originaux archivés
│       ├── AN.md / CRM.md / HR.md / ... (15 BCs)
│       └── INDEX.md
└── tasks/                             # Tasks non assignées (vide)
```

---

## Légende Statuts

| Icône | Statut | Description |
|-------|--------|-------------|
| 🔴 | To Do | Pas encore commencé |
| 🟡 | In Progress | En cours de réalisation |
| ⏸️ | Blocked | Bloqué par un obstacle |
| 🟢 | Done | Terminé |

### Workflow

```
🔴 To Do ──→ 🟡 In Progress ──→ 🟢 Done
     │              │
     │              ↓
     └────→ ⏸️ Blocked ←────┘
                │
                ↓
           🟡 In Progress
```
