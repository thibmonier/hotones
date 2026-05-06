# Sprint Review — Sprint 011

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 011 — DDD Phase 3 Completion + E2E Tests |
| Date Review | 2026-05-06 |
| Durée | 1 jour (mode agentic accéléré) |

## Sprint Goal

> "Compléter Phase 3 EPIC-001 par migration controllers Project + Order + Client edit/delete, ajouter test E2E, activer buffer DDD-PHASE1-INVOICE."

**Atteint : ✅ OUI** (17/17 pts, 100%, buffer activé)

## User Stories livrées

| ID | Pts | PR |
|---|---:|---|
| DDD-PHASE3-CLIENT-EDIT | 3 | #150 |
| DDD-PHASE3-PROJECT-CONTROLLER | 4 | #151 |
| DDD-PHASE3-ORDER-QUOTE-CONTROLLER | 4 | #152 |
| FUNCTIONAL-E2E-CLIENT-DDD | 2 | #153 |
| DDD-PHASE1-INVOICE (buffer) | 4 | #155 |
| **TOTAL** | **17** | **5 PRs** |

## Métriques

| Métrique | S-007 | S-008 | S-009 | S-010 | **S-011** |
|---|---:|---:|---:|---:|---:|
| Points livrés | 32 | 17 | 14 | 17 | **17** |
| Vélocité | 32 | 17 | 14 | 17 | **17** |
| Taux complétion | 100% | 100% | 93% | 100% | **100%** |
| PRs livrées | 10 | 8 | 6 | 5 | **5** |
| Régressions Unit | 0 | 0 | 0 | 0 | **0** |
| ADRs créées | 0 | 4 | 2 | 2 | **1** (0010) |

## Highlights

### EPIC-001 — toutes phases amorcées

- ✅ Phase 0 (foundation) — sprint-007
- ✅ Phase 1 (4 BCs additifs Client+Project+Order+Invoice) — sprints 008+011
- ✅ Phase 2 (3 BCs ACL Client+Project+Order) — sprints 009-010
- ✅ Phase 3 amorcée (3 controllers migrés) — sprints 010-011
- 🔲 Phase 4 (décommissionnement) — sprint-013+

### Bugs prod fixés via E2E

PR #153 a découvert et fixé 2 bugs prod via tests E2E:
1. Render template avec client null sur erreur validation
2. NoHandlerForMessageException bloquant le UC sur événement domain

→ Justifie l'investissement E2E tests même quand UC déjà testé en unit.

### Buffer activé pour la 1ère fois

Sprint-008 et 009 avaient defer le buffer. Sprint-011 active enfin
DDD-PHASE1-INVOICE (4 pts) grâce à la marge entre engagement (17) et
capacité (22).

## Stories deferred / queue sprint-012

| Item | Pts estimés |
|---|---:|
| DDD-PHASE2-INVOICE-ACL | 4 |
| DDD-PHASE3-INVOICE-CONTROLLER | 3 |
| DDD-PHASE2-VACATION-ACL | 4 |
| DDD-PHASE2-CONTRIBUTOR-ACL | 4 |
| DDD-PHASE4-DECOMMISSION-CLIENT | 3 (1ère décommission legacy) |
| TEST-COVERAGE-002 | 2 (escalator étape suivante) |

## Prochaines étapes

1. ✅ Sprint Review documenté
2. → Retro
3. → Sprint-012 kickoff
