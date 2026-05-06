# Sprint 009 — DDD Phase 2 Strangler Fig + Critical Fixes

## Informations

| Attribut | Valeur |
|---|---|
| Numéro | 009 |
| Début | 2026-05-06 |
| Fin | TBD (mode agentic accéléré) |
| Capacité estimée | 22 pts (médiane S-005..S-008) |
| Total engagé | **20 pts** |
| Animateur | Claude Opus 4.7 (1M context) |

## Sprint Goal

> "Démarrer Phase 2 EPIC-001 (Strangler Fig pattern via Anti-Corruption Layer) sur le BC Client comme premier candidat, fixer la régression critique multi-tenant TenantFilter, et résorber la dette test résiduelle de sprint-008 (TEST-MOCKS-005 + investigation Cat B/C)."

### Mesurabilité

- ✅ ADR Anti-Corruption Layer pattern documenté
- ✅ ACL Client BC opérationnel (use case CRUD via DDD vers Entity flat existante)
- ✅ TenantFilter `find()` regression résolue (4/4 tests TenantFilterRegressionTest PASS)
- ✅ PHPUnit Notices ≤ 100 (vs 251 baseline) après TEST-MOCKS-005
- ✅ Cat B (OnboardingTemplate) + Cat C (VacationApproval pending) investiguées et soit fixées soit ADR

---

## Sprint Backlog

### Sub-epic A — Critical Fix (2 pts)

| ID | Titre | Pts | Priorité |
|---|---|---:|---|
| **SEC-MULTITENANT-FIX-001** | Fix TenantFilter ne s'applique pas sur `find()` (régression sprint-007) | 2 | 🔴 Must |

### Sub-epic B — DDD Phase 2 Strangler Fig (8 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| ACL-ADR-001 | Pattern Anti-Corruption Layer documenté (ADR) | 1 | Foundation |
| DDD-PHASE2-CLIENT-ACL | Bridge `App\Entity\Client` ↔ `App\Domain\Client\Entity\Client` via ACL | 4 | 1er candidat ACL |
| DDD-PHASE2-USECASE-001 | Use case CRUD Client via DDD (CreateClientUseCase + 1 controller migré) | 3 | Validation pattern strangler |

### Sub-epic C — Tech Debt Resolution (5 pts)

| ID | Titre | Pts |
|---|---|---:|
| TEST-MOCKS-005 | Refactor shared setUp mocks → per-test factories | 3 |
| INVESTIGATE-CAT-B | Fix OnboardingTemplate fixtures (DOM crawler empty) | 1 |
| INVESTIGATE-CAT-C | Fix VacationApproval pending API queries (probable lié SEC-FIX-001) | 1 |

### Sub-epic D — Buffer (5 pts non engagés)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE1-INVOICE | Cherry-pick BC Invoice (déféré sprint-008) | 3 |
| TEST-COVERAGE-002 | Push coverage 25→30% (escalator sprint-006) | 2 |

---

## Sprint Backlog résumé

| Sub-epic | Pts | Stories |
|---|---:|---:|
| A — Critical Fix | 2 | 1 |
| B — DDD Phase 2 | 8 | 3 |
| C — Tech Debt | 5 | 3 |
| **Total engagé** | **15** | **7** |
| D — Buffer | 5 | 2 |
| **Capacité totale** | **20** | **9** |

> Engagement 15 pts. Capacité 20 pts. Marge 5 pts pour absorber complexité ACL pattern (première fois implémenté).

---

## Definition of Done

- [ ] Tests unitaires sur nouveau code (couverture ≥ 80%)
- [ ] PHPStan max sur sub-paths livrés (0 erreur réelle)
- [ ] Deptrac valide (Domain → no deps Infrastructure)
- [ ] ADR si décision architecturale
- [ ] Co-authored caveman commits
- [ ] PR créée vers main avec body structuré

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| ACL-ADR-001 | Aucune | 🟢 |
| DDD-PHASE2-CLIENT-ACL | ACL-ADR-001 + PR #131 (DDD Client) | 🟡 PR #131 OPEN |
| DDD-PHASE2-USECASE-001 | DDD-PHASE2-CLIENT-ACL | séquentiel |
| INVESTIGATE-CAT-C | SEC-MULTITENANT-FIX-001 (peut être lié) | 🟡 |
| TEST-MOCKS-005 | TOOLING-MOCK-SCRIPT (PR #126) | 🟡 PR #126 OPEN |

---

## Risques identifiés

| # | Risque | Probabilité | Impact | Mitigation |
|---|---|:-:|:-:|---|
| R-1 | ACL pattern complexe à implémenter (1ère fois) | Moyenne | Haut | Time-box ADR à 1 pt; déférer scope si trop ambitieux |
| R-2 | TenantFilter `find()` regression difficile à fixer | Faible | Haut | Investigation a déjà identifié hypothèses (cf ADR-0004) |
| R-3 | TEST-MOCKS-005 refactor invasif > 3 pts | Moyenne | Moyen | Time-box; déférer Strate suivante TEST-MOCKS-006 |
| R-4 | PRs sprint-008 (#126-#133) pas mergées entre temps | Moyenne | Moyen | Continuer base main, conflits auto-résolus pour Shared kernel |

---

## Prochaines étapes

1. ✅ Sprint Goal documenté (cette doc)
2. → `/project:decompose-tasks 009`
3. → `/project:run-sprint 009 --auto`
