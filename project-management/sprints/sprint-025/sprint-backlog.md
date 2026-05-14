# Sprint 025 — Sprint Backlog

> **Statut** : scope décidé (PRE-3 ✅), décomposition prête. Formalisation Sprint Planning P1 (2026-06-09).
> Engagement ferme **12 pts** — EPIC-003 Phase 5 (8 pts) + Sub-epic D dette (4 pts).

## Engagement ferme — 12 pts

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| 🔴 Must | US-114 | KPI Revenue forecast | 3 | 6 | 🔲 To Do |
| 🔴 Must | US-115 | KPI Taux de conversion devis → commande | 3 | 6 | 🔲 To Do |
| 🟡 Should | US-116 | Extension widgets DSO/lead time (drill-down + CSV) | 2 | 4 | 🔲 To Do |
| 🔴 Must | MAGO-LINT-BATCH-001 | Mago lint cleanup batch initial | 2 | 3 | 🔲 To Do |
| 🟡 Should | VACATION-REPO-AUDIT | Audit Deptrac VacationRepository | 1 | 2 | 🔲 To Do |
| 🟡 Should | TEST-COVERAGE-013 | Coverage 70 → 72 % | 1 | 2 | 🔲 To Do |

**Total engagé : 12 / 12 pts ferme · 23 tâches · ~43h**

## Cap libre (PRE-5 / A-2) — 1-2 pts

| Slot | Story | Points | Statut |
|---|---|---:|---|
| Libre | _À assigner Sprint Planning P1 — pas de slot reserved vide_ | 1-2 | TBD |

> Candidats : item dette résiduelle (post Mago batch), ou amorçage US-117.
> US-117 (3 pts) trop gros pour cap libre → reste backlog Phase 5.

## Reporté → backlog Phase 5 (sprint-026+)

| ID | Titre | Points | Raison |
|---|---|---:|---|
| US-117 | KPI Marge moyenne portefeuille | 3 | overflow capacité sprint-025 |

## Répartition par couche

| Couche | Tâches | Heures |
|---|---:|---:|
| [BE] | 11 | 23h |
| [FE-WEB] | 3 | 6h |
| [TEST] | 5 | 9h |
| [OPS] | 3 | 3h |
| **TOTAL** | **23** | **43h** |

## Dépendances

| Item | Dépend de | Statut |
|---|---|---|
| US-114 / US-115 | Pattern KpiCalculator + `cache.kpi` + `SlackAlertingService` (sprint-024) | ✅ livré |
| US-116 | Read-models US-110/111 + `DriftReportCsvExporter` US-113 | ✅ livré |
| Sub-epic D | Aucune (indépendant) | ✅ |
| US-114/115/116 entre eux | Indépendants — parallélisables | ✅ |

## Sprint Planning P1 — points à acter

- [ ] Sprint Goal figé (1 phrase)
- [ ] Engagement 12 pts confirmé par l'équipe
- [ ] PRE-5 : cap libre 1-2 pts assigné à une story concrète
- [ ] A-1 : `enablePullRequestAutoMerge` activé (J-2)
- [ ] A-5 : fenêtre maintenance J0 planifiée pour T-113-07 dry-run prod
- [ ] Dossier `sprint-025` renommé `sprint-025-epic-003-phase-5-kpi`
