# Sprint Retrospective — Sprint 011

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 011 — DDD Phase 3 Completion + E2E Tests |
| Date | 2026-05-06 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Pattern réplication controller** Client → Project → Order. Effort prévisible (~3-4 pts par BC). |
| K-2 | **E2E tests apportent valeur** au-delà de ce que tests Unit couvrent (intégration template + container + Doctrine). 2 bugs prod fixés via PR #153. |
| K-3 | **Buffer activable** quand engagement < capacité (sprint-011 = 1ère activation buffer). |
| K-4 | **State machine demos** dans controllers (`change-status-via-ddd`) montrent valeur DDD vs legacy. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | Foundation cherry-pick continuent (5ème PR Phase 1+2 avec mêmes Shared kernel) | Sprint-012: stack PRs sur `feat/ddd-foundation-stabilized` quand foundation stable mergée. |
| L-2 | Tests Unit Use Cases Create* manquent (CreateClientUC, CreateProjectUC, CreateOrderQuoteUC) | Sprint-012: ajouter via test unitaire avec mocks EM ou abandonner et compter sur E2E (PR #153 a déjà couvert Client). |

### MORE OF

| # | Item |
|---|---|
| M-1 | **Migration controller progressive** (`/X/edit-via-ddd`, `/X/delete-via-ddd`) éprouvée. Réplication possible Vacation/Contributor/Invoice sprints suivants. |
| M-2 | **Domain events graceful** via try/catch NoHandlerForMessageException = pattern Phase 2/3 valide (handlers viendront Phase 4+). |

### STOP

| # | Item |
|---|---|
| S-1 | Cherry-pick foundation files dans CHAQUE PR. C'est devenu un anti-pattern à 6 sprints d'affilée. Stack ou utiliser branche commune dès sprint-012. |

### START

| # | Item |
|---|---|
| ST-1 | **Phase 4 démarrer**: 1ère décommission de route legacy (sprint-012-013). Critère: feature parity DDD route validée + UAT OK. |
| ST-2 | **Coverage TEST-COVERAGE-002** (escalator 25→30%) après 4 sprints sans avancement |
| ST-3 | **PR cleanup** — la branche actuelle (chore/sprint-010-review-retro-and-sprint-011-kickoff) date de l'entrée dans cette session. ~57 PRs ouvertes sur main = il faut commencer à les merger. |

---

## Action items sprint-012

| # | Action | Pts |
|---|---|---:|
| A-1 | DDD-PHASE2-INVOICE-ACL | 4 |
| A-2 | DDD-PHASE3-INVOICE-CONTROLLER | 3 |
| A-3 | DDD-PHASE4-DECOMMISSION-CLIENT (1ère) | 3 |
| A-4 | TEST-COVERAGE-002 (étape 25→30%) | 2 |
| A-5 | DDD-FOUNDATION-STABILIZED branche commune | (process) |

---

## Métriques rétro

| Métrique | S-007 | S-008 | S-009 | S-010 | **S-011** |
|---|---:|---:|---:|---:|---:|
| Vélocité | 32 | 17 | 14 | 17 | **17** |
| Taux complétion | 100% | 100% | 93% | 100% | **100%** |
| Régressions production | 0 | 0 | 0 | 0 | **0** |
| Buffer activé | non | non | non | non | **OUI** |

---

## Highlights sprint-011

- 🎉 **Sprint-011 100% complet** (17/17 pts, buffer activé)
- 🏗️ Phase 3 EPIC-001: 3 controllers migrés (Client + Project + Order)
- 🐛 2 bugs prod fixés via E2E tests
- 📝 ADR-0010 (BC Invoice coexistence)
- 0 régression sur 5 PRs

## Sentiment

```
😊 Très satisfait    [█████████████████░░░] 88%   → 17/17 + buffer + 0 régression
😐 Mixte             [██░░░░░░░░░░░░░░░░░░] 10%   → Foundation duplication continue
😞 Frustré           [▌░░░░░░░░░░░░░░░░░░░] 2%   → 57 PRs ouvertes accumulées
```
