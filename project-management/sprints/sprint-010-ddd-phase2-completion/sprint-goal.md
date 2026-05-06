# Sprint 010 — DDD Phase 2 Completion + Phase 3 Start

## Informations

| Attribut | Valeur |
|---|---|
| Numéro | 010 |
| Début | 2026-05-06 |
| Fin | TBD (mode agentic accéléré) |
| Capacité estimée | 18 pts (médiane S-005..S-009) |
| Total engagé | **18 pts** |
| Animateur | Claude Opus 4.7 (1M context) |

## Sprint Goal

> "Compléter Phase 2 EPIC-001 en répliquant le pattern ACL sur Project + Order BCs (3 BCs ACL au total), démarrer Phase 3 avec le 1er controller migré vers les use cases DDD, et résorber la dette résiduelle (Cat C functional fix)."

### Mesurabilité

- ✅ ACL Project BC opérationnel (4 pts pattern réplication)
- ✅ ACL Order BC opérationnel (4 pts pattern réplication)
- ✅ 1 controller migré vers use cases DDD (validation Phase 3)
- ✅ INVESTIGATE-CAT-C résolu (sprint-009 déféré)
- ✅ 0 régression suite Unit

---

## Sprint Backlog

### Sub-epic A — DDD Phase 2 Completion (12 pts)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE2-PROJECT-ACL | Réplication ACL pattern sur Project BC | 4 |
| DDD-PHASE2-ORDER-ACL | Réplication ACL pattern sur Order BC | 4 |
| DDD-PHASE2-USECASE-002 | Use cases Project (Create/Update) + Order (CreateOrderQuote) | 4 |

### Sub-epic B — Phase 3 Start (4 pts)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE3-CONTROLLER-MIGRATION | Migration de `ClientController::create()` vers `CreateClientUseCase` | 4 |

### Sub-epic C — Tech Debt (1 pt)

| ID | Titre | Pts |
|---|---|---:|
| INVESTIGATE-CAT-C | Fix VacationApproval pending API (déféré sprint-009) | 1 |

### Sub-epic D — Buffer (5 pts non engagés)

- DDD-PHASE1-INVOICE (3 pts)
- TEST-COVERAGE-002 (2 pts)

---

## Sprint Backlog résumé

| Sub-epic | Pts | Stories |
|---|---:|---:|
| A — DDD Phase 2 Completion | 12 | 3 |
| B — Phase 3 Start | 4 | 1 |
| C — Tech Debt | 1 | 1 |
| **Total engagé** | **17** | **5** |
| Buffer | 5 | 2 |
| **Capacité totale** | **22** | **7** |

> Note: 17 pts engagement + 5 pts buffer. Engagement aligné avec vélocité médiane (17-19 pts). Le buffer permet activation opportuniste si Phase 3 controller migration plus rapide que prévu.

---

## Definition of Done

- [ ] Pattern ACL réutilisé fidèlement (foundation, translators, repository adapter, use cases)
- [ ] Tests Unit ≥ 80% sur code livré
- [ ] PHPStan max → 0 erreur réelle
- [ ] Deptrac valide
- [ ] ADR si décision architecturale (probable: ADR-0009 sur controller migration pattern)
- [ ] Commits caveman + co-authored

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| DDD-PHASE2-PROJECT-ACL | DDD-PHASE1-PROJECT (PR #132) | 🟡 OPEN |
| DDD-PHASE2-ORDER-ACL | DDD-PHASE1-ORDER (PR #133) | 🟡 OPEN |
| DDD-PHASE2-USECASE-002 | Project + Order ACLs | séquentiel |
| DDD-PHASE3-CONTROLLER-MIGRATION | DDD-PHASE2-USECASE-001 (PR #141) | 🟡 OPEN |
| INVESTIGATE-CAT-C | SEC-MULTITENANT-FIX-001 (PR #136) | 🟡 OPEN |

---

## Risques identifiés

| # | Risque | Probabilité | Impact | Mitigation |
|---|---|:-:|:-:|---|
| R-1 | Order BC ACL plus complexe (3 entities) | Haute | Moyen | Time-box; déférer OrderSection ACL si nécessaire |
| R-2 | Controller migration casse fonctionnalité existante | Moyenne | Haut | Test functional E2E avant migration; rollback facile |
| R-3 | Foundation cherry-pick rebase conflicts | Moyenne | Bas | Stack PRs sur foundation OU continuer cherry-pick (auto-résolu au merge) |

---

## Prochaines étapes

1. ✅ Sprint Goal documenté
2. → `/project:decompose-tasks 010`
3. → `/project:run-sprint 010 --auto`
