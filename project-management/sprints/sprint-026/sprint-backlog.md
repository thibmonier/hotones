# Sprint 026 — Sprint Backlog

> **Statut** : scope décidé (PRE-3 ✅), décomposition prête. Formalisation Sprint Planning P1 (2026-06-23).
> Engagement ferme **12 pts** — Phase 5 (5 pts) + Dette résiduelle (6 pts) + Migration prod (1 pt).

## Engagement ferme — 12 pts

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| 🔴 Must | US-117 | KPI Marge moyenne portefeuille | 3 | 6 | 🔲 To Do |
| 🟡 Should | US-119 | Extension drill-down Conversion + Margin | 2 | 4 | 🔲 To Do |
| 🔴 Must | MAGO-LINT-BATCH-002 | Cleanup Mago résiduel (200-300 ciblées) | 2 | 3 | 🔲 To Do |
| 🟡 Should | COVERAGE-014 | Push coverage 72 → 74 % | 2 | 2 | 🔲 To Do |
| 🟡 Should | TEST-FUNCTIONAL-FIXES-003 | Audit `skip-pre-push` markers restants | 2 | 2 | 🔲 To Do |
| 🔴 Must | T-113-07 | Dry-run prod migration WorkItem.cost legacy | 1 | 2 | 🔲 To Do |

**Total engagé : 12 / 12 pts ferme · 19 tâches · ~35h**

> **Cap libre PRE-5** : intégré au ferme via T-113-07 — A-1 HIGH sp-025 retro satisfait, pas de slot reserved vide (4ᵉ fois TBD évité).

## Répartition par couche

| Couche | Tâches | Heures |
|---|---:|---:|
| [BE] | 8 | 16h |
| [FE-WEB] | 2 | 3h |
| [TEST] | 5 | 11h |
| [OPS] | 4 | 5h |
| **TOTAL** | **19** | **35h** |

## Dépendances

| Item | Dépend de | Statut |
|---|---|---|
| US-117 | US-107 (Project.margin snapshot) + US-112 (MarginAdoptionCalculator partiel réutilisé) | ✅ livré sp-023/024 |
| US-119 | US-115/US-112 read-models + US-116 controller drill-down | ✅ livré sp-024/025 |
| Sub-epic D items | Indépendants | ✅ |
| T-113-07 | US-113 (WorkItemMigrator + commande migrate-legacy-cost) | ✅ livré sp-024 |
| Stories entre elles | Largement indépendantes — parallélisables | ✅ |

## Sprint Planning P1 — points à acter

- [ ] Sprint Goal figé (1 phrase)
- [ ] Engagement 12 pts confirmé par l'équipe
- [ ] PRE-5 cap libre : ✅ acté (T-113-07 dans le ferme)
- [ ] A-1 sp-024 : `enablePullRequestAutoMerge` settings — 3ᵉ fois héritée, traiter
- [ ] A-7 : décision Slack channel `#kpi-alerts-prod` (atelier OPS-PREP)
- [ ] T-113-07 : fenêtre maintenance prod planifiée
- [ ] Dossier `sprint-026` renommé `sprint-026-epic-003-phase-5-continuation`
