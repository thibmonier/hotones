# Backlog Index

> Dernière mise à jour: 2026-05-11

---

## Résumé Global

| Type | 🔴 To Do | 🟡 In Progress | ⏸️ Blocked | 🟢 Done | Total |
|------|----------|----------------|------------|---------|-------|
| EPICs | 0 | 1 | 0 | 2 | 3 |
| User Stories | ~70 | 1 | 0 | ~29 | 100 |
| Tasks (in sprints) | — | — | — | — | 58 |

> Statuts US dérivés livraisons git sprint-001..023. Reste 70 stories
> non démarrées dans backlog/user-stories/US-*.md (split 2026-05-11
> depuis BC files, voir `by-bc-archive/`).

---

## EPICs

| ID | Nom | Statut | Priorité | US | Progression |
|----|-----|--------|----------|-----|-------------|
| EPIC-001 | Migration Clean Architecture DDD | 🟢 Done | High | ~15 (sprint-007..014) | 100 % (4 phases livrées) |
| EPIC-002 | Observability & Performance | 🟢 Done | High | US-091..US-095 | 100 % (sprints 016-018, 124-130 % engagement) |
| EPIC-003 | WorkItem & Profitability | 🟡 In Progress | High | US-097..US-108 + Phase 4 planifiée | Phase 3 finition (sprint-023, 5ᵉ sprint sur 6 estimés) |

---

## Sprint Actuel

**sprint-024 — EPIC-003 Phase 4 Kickoff (KPIs business) — kickoff pending**

| Champ | Valeur |
|---|---|
| Période | 2026-05-27 → 2026-06-10 (10 jours ouvrés) |
| Capacité | 12 pts ferme + 1-2 pts libre (4ᵉ confirmation recalibrage durable) |
| Goal | KPIs business (DSO + temps facturation + adoption marge). Décision PO PRE-5 Render redeploy 6ᵉ sprint consécutif holdover (trigger atteint). |
| Stories engagées (provisoire) | US-110/111/112/113 + OPS-PRE5-DECISION |
| Statut | kickoff_pending — Sprint Planning P1 à 2026-05-27 |

Voir : `project-management/sprints/sprint-024-epic-003-phase-4-kickoff/sprint-goal.md`

### Sprint Précédent — sprint-023 ✅ CLOSED 2026-05-11

| Champ | Valeur |
|---|---|
| Engagement | 12 pts ferme |
| Livré | **12 pts (100 %)** |
| Stories | US-106 + US-107 + US-108 + COVERAGE-012 + INTEGRATION-21-SUITE |
| Highlights | Strangler fig EPIC-003 Phase 3 complet (LowMarginAlertEvent supprimé), persistence margin snapshot, configurabilité hiérarchique seuil, 3ᵉ sprint consécutif 0 holdover OPS, coverage 70 % atteint, 0 commit `--no-verify` |

Voir : `project-management/sprints/sprint-023-epic-003-phase-3-finition/sprint-review.md` + `sprint-retro.md`

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
| sprint-024 | EPIC-003 Phase 4 Kickoff (kickoff pending) | TBD | — | — |

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
│   └── EPIC-003-workitem-and-profitability.md        🟡
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
