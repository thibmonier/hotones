# Sprint 027 — Sprint Backlog

> **Statut** : scope_figed (Planning P1 2026-05-16). Prêt développement.
> Engagement ferme **12 pts** — 8 pts features UX + 1 pt ops migration + 3 pts dette ciblée.

## Engagement ferme — 12 pts

### Features EPIC-003 Phase 6 (8 pts)

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| 🔴 Must | US-120 | UX refonte dashboard 9 KPIs (responsivité mobile + ordre lisible) | 8 | TBD Planning P2 | 🔲 To Do |

### Ops migration prod (1 pt — A-1 HIGH sp-026 retro)

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| 🔴 Must | T-113-07 | Dry-run prod migration WorkItem.cost legacy | 1 | 2 | 🔲 To Do (`requires:ops-human`) |

**Fenêtre maintenance prod : semaine 2 sp-027 (J5-J10)** — atelier OPS-PREP-J0 J-2.

### Sub-epic D dette ciblée (3 pts)

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| 🟡 Should | MAGO-LINT-BATCH-003 | Cleanup assertion-style 113 fixes — **3 PRs split** | 2 | 3 | 🔲 To Do |
| 🟡 Should | OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK | Script + Makefile + hook retro + alerte Slack 24h | 1 | 4 | 🔲 To Do |

**Total engagé : 12 / 12 pts ferme · 4 stories · ~22 tâches (TBD US-120 Planning P2)**

## Stories reportées sp-028

| ID | Titre | Points | Raison report |
|---|---|---:|---|
| KPI-TEST-SUPPORT-TRAIT | Helper trait Multi-tenant + cache + setUp | 1 | Priorité UX > refactor (3ᵉ report A-3 sp-025) |
| OPS-DEPENDENCY-FRESHNESS-CHECK | Script audit composer + yarn + cron Slack hebdo | 1 | Priorité UX > process complementary |
| Slack `#kpi-alerts-prod` (A-7 sp-024..026) | Channel création | — | **5ᵉ tentative sp-028 — ADR-0019 Out Backlog si nouvel échec** |

## Répartition par couche (estimation pré-Planning P2)

| Couche | Tâches estim | Heures estim |
|---|---:|---:|
| [FE-WEB] (UX refonte) | ~10 | ~18h |
| [BE] | 2 | 3.5h |
| [OPS] | 5 | 5h |
| [TEST] | 3 | 5h |
| [DOC] | 1 | 0.5h |
| **TOTAL** | **~21** | **~32h** |

## Dépendances

| Item | Dépend de | Statut |
|---|---|---|
| US-120 UX refonte | Wireframes + design tokens existants + feedback stakeholders sp-026 | 🟡 atelier UX J-3 |
| T-113-07 | Fenêtre maintenance prod + accès Tech Lead + backup BDD | ⚠️ OPS-PREP-J0 J-2 |
| MAGO-LINT-BATCH-003 | Auto-merge activé (✅ 2026-05-16) + label PR `mago-unsafe-review` | ✅ ready |
| OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK | Accès DB prod read-only ou `doctrine:migrations:status --env=prod` | ⚠️ Tech Lead |

## Sprint Planning P2 — points à acter

- [ ] US-120 décomposition tâches (atelier wireframes pré-J0)
- [ ] T-113-07 créneau fenêtre maintenance ferme (jour+heure)
- [ ] MAGO-LINT-BATCH-003 split PRs Unit→Integration→Functional ordre
- [ ] OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK accès DB prod modalité
- [ ] Dossier `sprint-027` renommé `sprint-027-ux-refonte-dashboard-phase-6`
- [ ] Tag `requires:ops-human` métadata format YAML défini

## Risques

| Risque | Probabilité | Mitigation |
|---|---|---|
| US-120 UX refonte sous-estimée 8 pts | Moyenne | Wireframes pré-Planning P1 + scoping atelier UX |
| T-113-07 fenêtre maintenance non confirmée | Faible | Atelier OPS-PREP-J0 J-2 ferme |
| Mago 3 PRs split conflit séquentiel | Moyenne | Stack PRs ordre + auto-merge activé |

---

**Auteur** : Tech Lead
**Date** : 2026-05-16 (Planning P1 figé)
**Version** : 1.0.0
