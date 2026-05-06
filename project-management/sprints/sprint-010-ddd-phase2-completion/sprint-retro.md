# Sprint Retrospective — Sprint 010

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 010 — DDD Phase 2 Completion + Phase 3 Start |
| Date | 2026-05-06 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item | Justification |
|---|---|---|
| K-1 | **Pattern ACL réplication** | Client → Project → Order chacun en ~4 pts. Pattern stable, pas de surprise. |
| K-2 | **"Augmenter ne pas remplacer" Phase 3** | 0 risque de régression sur la prod. Route DDD coexiste avec legacy. |
| K-3 | **One-liner fix Cat C** | Investigation profonde révèle bug architectural mineur (inverse-side). Fix simple > refactor invasif. |
| K-4 | **ADRs systematiques** | ADR-0009 documente Phase 3 pattern AVANT implémentation. Permet code review structuré. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Foundation duplication continue** | Sprint-011: créer une branche `feat/ddd-foundation-stabilized` pour stack TOUS les futurs PRs DDD. |
| L-2 | **Tests d'intégration ACL absents** | Translators + ACL repos testés en unit. Pas de test E2E avec vraie DB. Sprint-011 candidate. |

### MORE OF

| # | Item |
|---|---|
| M-1 | **Pattern réplication par BC** = effort prévisible. Estimation des futurs ACLs (Invoice, Vacation, etc.) = ~4 pts chacun avec confiance. |
| M-2 | **State machines DDD apportent valeur métier** réelle (Project + Order). Anti-corruption majeure vs Entity flat permissive. |

### STOP

| # | Item |
|---|---|
| S-1 | **Cherry-pick fichier-par-fichier** dans chaque PR. Stabiliser une foundation branche commune Sprint-011. |

### START

| # | Item |
|---|---|
| ST-1 | Test functional E2E `/clients/new-via-ddd` (sprint-011 candidate) |
| ST-2 | Branche foundation `feat/ddd-foundation-stabilized` |
| ST-3 | Migrer ClientController::edit() vers DDD UpdateClientUseCase déjà existant (PR #141) |

---

## Action items sprint-011

| # | Action | Pts |
|---|---|---:|
| A-1 | DDD-PHASE3-CLIENT-EDIT (edit + delete via UC) | 3 |
| A-2 | DDD-PHASE3-PROJECT-CONTROLLER | 4 |
| A-3 | DDD-PHASE3-ORDER-QUOTE-CONTROLLER | 4 |
| A-4 | Functional E2E test `/clients/new-via-ddd` | 2 |
| A-5 | Foundation stabilized branche commune | (process) |
| A-6 | Buffer: DDD-PHASE1-INVOICE OU TEST-COVERAGE-002 | 3-2 |

---

## Métriques rétro

| Métrique | S-007 | S-008 | S-009 | **S-010** | Tendance |
|---|---:|---:|---:|---:|:-:|
| Vélocité | 32 | 17 | 14 | **17** | ↗️ |
| Taux complétion | 100% | 100% | 93% | **100%** | ↗️ |
| Régressions production | 0 | 0 | 0 | **0** | = |
| ADRs créées | 0 | 4 | 2 | **2** | — |

---

## Highlights sprint-010

- 🎉 **EPIC-001 Phase 2 100% complet** (3 BCs ACL + use cases)
- 🏗️ **Phase 3 amorcée** (1er controller migré)
- ⚡ Cat C résolu (one-liner fix)
- 0 régression sur 5 PRs

## Sentiment équipe

```
😊 Très satisfait    [██████████████████░░] 92%   → 17/17 livrés, Phase 2 ✅
😐 Mixte             [█░░░░░░░░░░░░░░░░░░░] 6%   → Foundation duplication continue
😞 Frustré           [▌░░░░░░░░░░░░░░░░░░░] 2%   → Tests E2E absents
```
