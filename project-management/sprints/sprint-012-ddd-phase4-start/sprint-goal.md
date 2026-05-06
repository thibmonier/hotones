# Sprint 012 — DDD Phase 4 Start + Invoice Completion + Coverage

## Informations

| Attribut | Valeur |
|---|---|
| Numéro | 012 |
| Capacité | 22 pts (médiane historique) |
| Total engagé | **17 pts** |
| Marge buffer | 5 pts |

## Sprint Goal

> "Démarrer Phase 4 EPIC-001 par la 1ère décommission de route legacy (ClientController), compléter la chaîne DDD Invoice (ACL + controller), avancer l'escalator coverage TEST-COVERAGE-002 (25→30%), et stabiliser le pattern foundation pour stop la duplication cherry-pick."

---

## Sprint Backlog (17 pts)

### Sub-epic A — DDD Phase 4 Start (3 pts)

| ID | Titre | Pts |
|---|---|---:|
| **DDD-PHASE4-DECOMMISSION-CLIENT-NEW** | Promote `/clients/new-via-ddd` → `/clients/new` (legacy supprimée). 1ère décommission Phase 4. | 3 |

### Sub-epic B — Invoice ACL completion (7 pts)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE2-INVOICE-ACL | ACL adapter + 2 translators InvoiceFlatToDdd + InvoiceDddToFlat | 4 |
| DDD-PHASE3-INVOICE-CONTROLLER | InvoiceController routes /invoices/new-via-ddd + /invoices/{id}/edit-via-ddd | 3 |

### Sub-epic C — Tech Debt + Process (5 pts)

| ID | Titre | Pts |
|---|---|---:|
| TEST-COVERAGE-002 | Push coverage 25→30% (escalator step 2 sur 5) | 2 |
| FOUNDATION-STABILIZED | Branche `feat/ddd-foundation-stabilized` commune pour stop cherry-pick | 1 |
| TEST-MOCKS-006 | Continuer reductions notices après PRs sprint-008/009 mergées | 2 |

### Sub-epic D — Buffer (5 pts non engagés)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE2-CONTRIBUTOR-ACL | Bridge Contributor BC | 4 |
| DDD-PHASE2-VACATION-ACL | Bridge Vacation BC déjà DDD partiellement | 4 |

---

## Definition of Done

- ✅ Tests unitaires sur nouveau code
- ✅ PHPStan max → 0 erreur réelle
- ✅ ADR si décision architecturale
- ✅ Aucune régression suite Unit
- ✅ Phase 4 décommission validée par feature parity

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| DDD-PHASE4-DECOMMISSION-CLIENT-NEW | All Client routes mergées (PRs #140, #141, #148, #150, #153) | 🟢 mergées |
| DDD-PHASE2-INVOICE-ACL | DDD-PHASE1-INVOICE (PR #155 mergé) | 🟢 |
| DDD-PHASE3-INVOICE-CONTROLLER | DDD-PHASE2-INVOICE-ACL | séquentiel |
| TEST-COVERAGE-002 | Audit T-TC1-01 PR #107 | 🟢 |
| FOUNDATION-STABILIZED | Phase 1 + Shared kernel mergés | 🟢 |

---

## Risques

| # | Risque | Mitigation |
|---|---|---|
| R-1 | Décommission Phase 4 = breaking change si feature parity incomplète | Tests E2E avant suppression + rollback rapide via git revert |
| R-2 | Invoice ACL plus complexe (4 events vs 1) | Pattern réplicable depuis Order ACL (qui a aussi 2 events). Time-box 4 pts. |
| R-3 | Coverage 25→30% nécessite ~5% absolu = ~50 LOC tests par % | Cibler high-LOC services (cf audit T-TC1-01) |

---

## Phase 4 — Critères de promotion route DDD → main route

(Cf ADR-0009)

Une route `/X-via-ddd` peut REMPLACER `/X` quand:
- ✅ Tests E2E couvrent les use cases legacy (PR #153 a couvert /clients/new+edit+delete)
- ✅ Toutes fonctionnalités legacy couvertes (logo upload reste hors scope DDD pour Client — décision: déplacer logo en service séparé Phase 4)
- ✅ Code review valide
- ✅ Aucune régression UAT

Pour ClientController::new specifically:
- Logo upload non couvert par DDD UC → 2 options:
  1. Garder route legacy /new pour les uploads, /new-via-ddd devient principal pour les autres
  2. Créer un endpoint séparé `/clients/{id}/upload-logo` post-création

**Décision recommandée Phase 4 sprint-012**: Option 2 (séparation des concerns).

---

## Prochaines étapes

1. ✅ Sprint Goal documenté
2. → `/project:run-sprint 012`
