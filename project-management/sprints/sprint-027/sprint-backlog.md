# Sprint 027 — Sprint Backlog

> **Statut** : kickoff_pending (scope partiel décidé sp-026 retro). Formalisation Sprint Planning P1.
> Engagement provisoire **12 pts** — 6 pts ops/dette confirmés + 6 pts features TBD.

## Engagement provisoire — 12 pts

### Ops migration prod (1 pt confirmé)

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| 🔴 Must | T-113-07 | Dry-run prod migration WorkItem.cost legacy | 1 | 2 | 🔲 To Do |

### Sub-epic D dette résiduelle (5 pts confirmés)

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| 🟡 Should | MAGO-LINT-BATCH-003 | Cleanup assertion-style 113 fixes (`--unsafe` PR preview review) | 2 | 3 | 🔲 To Do |
| 🟡 Should | OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK | Script + Makefile + hook retro + alerte Slack | 1 | 4 | 🔲 To Do |
| 🟡 Should | OPS-DEPENDENCY-FRESHNESS-CHECK | Script audit composer + yarn + cron Slack hebdo | 1 | 4 | 🔲 To Do |
| 🟢 Could | KPI-TEST-SUPPORT-TRAIT | Helper trait Multi-tenant + cache + setUp (refactor) | 1 | 1 | 🔲 To Do |

### Features TBD (6 pts) — Planning P1

| Priorité | ID | Titre | Points | Tâches | Statut |
|---|---|---|---:|---:|---|
| TBD | ??? | TBD Planning P1 (EPIC-003 Phase 6 OU nouvel EPIC OU UX refonte dashboard) | 6 | TBD | 🔲 TBD |

**Total provisoire engagé : 6 pts ferme + 6 pts TBD = 12 / 12 pts cible**

## Répartition provisoire par couche (sous réserve scope TBD)

| Couche | Tâches confirmées | Heures |
|---|---:|---:|
| [BE] | 2 | 3.5h |
| [OPS] | 8 | 10h |
| [TEST] | 1 | 3h |
| [DOC] | 1 | 0.5h |
| **TOTAL confirmé** | **12** | **17h** |
| TBD (6 pts features) | ~10 | ~18h |
| **TOTAL cible** | **~22** | **~35h** |

## Dépendances

| Item | Dépend de | Statut |
|---|---|---|
| T-113-07 | Fenêtre maintenance prod planifiée + accès Tech Lead + backup BDD | ⚠️ J-2 OPS-PREP |
| MAGO-LINT-BATCH-003 | Approval explicit `--unsafe` (label PR `mago-unsafe-review`) | ⚠️ PO + Tech Lead Planning P1 |
| OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK | Accès DB prod read-only ou SSH-tunnel | ⚠️ Tech Lead |
| OPS-DEPENDENCY-FRESHNESS-CHECK | Slack channel `#tech-lead-digest` créé | ⚠️ A-6 Planning P1 |
| KPI-TEST-SUPPORT-TRAIT | Indépendant — extraction refactor | ✅ |
| Features TBD | Scope identifié Planning P1 | ❌ atelier scope J-3 obligatoire |

## Sprint Planning P1 — points à acter

- [ ] Sprint Goal figé (1 phrase)
- [ ] Scope features TBD identifié (6 pts)
- [ ] Engagement 12 pts confirmé par équipe
- [ ] T-113-07 fenêtre maintenance prod ferme avec date+heure
- [ ] A-2 décision tag `requires:ops-human` ou exclusion engagement-ratio
- [ ] A-3 approval `--unsafe` Mago + label PR
- [ ] A-5 décision `batch-queue.yaml` (gitignore vs single PR vs auto-merge)
- [ ] A-6 Slack `#kpi-alerts-prod` création (4ᵉ tentative — décision PO+TL)
- [ ] Décompte ops-bloquant des pts engagement-ratio (L-3 sp-026 retro)
- [ ] Dossier `sprint-027` renommé selon scope figé (ex: `sprint-027-ops-dette-phase-6` ou autre)

## Risques

| Risque | Probabilité | Mitigation |
|---|---|---|
| T-113-07 4ᵉ report si fenêtre non confirmée | Moyenne | Atelier OPS-PREP J-2 ferme + créneau accepté |
| 6 pts features TBD non identifiés J0 | Faible | Atelier scope J-3 obligatoire (PO + Tech Lead) |
| Mago `--unsafe` mass diff 113 fichiers tests | Moyenne | Split en 3 PRs (Unit / Integration / Functional) |
| OPS scripts nécessitent accès prod restreint | Faible | SSH-tunnel ou `doctrine:migrations:status --env=prod` read-only |

---

**Auteur** : Tech Lead
**Date** : 2026-05-16 (kickoff stub)
**Version** : 0.1.0 (provisoire)
