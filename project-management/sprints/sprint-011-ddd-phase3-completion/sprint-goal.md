# Sprint 011 — DDD Phase 3 Completion + E2E Tests

## Informations

| Attribut | Valeur |
|---|---|
| Numéro | 011 |
| Capacité estimée | 19 pts (médiane S-005..S-010) |
| Total engagé | **17 pts** |

## Sprint Goal

> "Compléter Phase 3 EPIC-001 par la migration des controllers Project + Order + Client edit/delete vers les use cases DDD, ajouter test E2E sur la route DDD livrée sprint-010, et activer le buffer DDD-PHASE1-INVOICE."

---

## Sprint Backlog (17 pts)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE3-CLIENT-EDIT | `/clients/{id}/edit-via-ddd` + delete | 3 |
| DDD-PHASE3-PROJECT-CONTROLLER | ProjectController create + edit migration | 4 |
| DDD-PHASE3-ORDER-QUOTE-CONTROLLER | OrderController create quote migration | 4 |
| FUNCTIONAL-E2E-CLIENT-DDD | Test E2E `/clients/new-via-ddd` round-trip | 2 |
| FOUNDATION-STABILIZED-BRANCH | Process: branche commune pour stack PRs DDD | (process) |
| DDD-PHASE1-INVOICE | Buffer activé: cherry-pick BC Invoice | 4 |

---

## Risques

| # | Risque | Mitigation |
|---|---|---|
| R-1 | Templates Twig incompatibles avec UC (form fields différents) | Garder routes legacy en parallèle, migration progressive |
| R-2 | Order quote migration plus complexe (relation Project) | Time-box 4 pts, déférer signature workflow sprint-012 |
| R-3 | E2E test fragile (DB+session+Doctrine fixtures) | Marker `skip-pre-push` si nécessaire (cf ADR-0003) |

---

## Prochaines étapes

1. ✅ Sprint Goal documenté
2. → Décomposition tasks
3. → Run sprint
