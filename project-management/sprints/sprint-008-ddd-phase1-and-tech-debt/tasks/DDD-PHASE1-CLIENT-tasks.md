# DDD-PHASE1-CLIENT — Tasks

> Cherry-pick BC Client depuis `proto/ddd-baseline-2026-01-19` (3 pts).
> 6 tasks / ~12h.

## Story rappel

Resoudre **GAP-C5** (BC stub vide). Cherry-pick l'Entity DDD `App\Domain\Client\*` du prototype, sans toucher l'Entity flat `App\Entity\Client` actuelle (coexistence).

## Files attendus (depuis tag `proto/ddd-baseline-2026-01-19`)

```
src/Domain/Client/
├── Entity/Client.php
├── Event/ClientCreatedEvent.php
├── Exception/ClientNotFoundException.php
├── Repository/ClientRepositoryInterface.php
├── ValueObject/ClientId.php
├── ValueObject/CompanyName.php
└── ValueObject/ServiceLevel.php

src/Infrastructure/Persistence/Doctrine/
├── Mapping/Client/Client.orm.xml
├── Type/ClientIdType.php
├── Type/CompanyNameType.php
└── Type/ServiceLevelType.php
```

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-DDP1C-01 | [DDD] | Cherry-pick 7 fichiers `src/Domain/Client/` depuis tag proto. PHPStan max sur src/Domain/Client/ → 0 erreur | 2h | DDD-PHASE0-002 | 🔲 |
| T-DDP1C-02 | [DDD] | Cherry-pick 4 fichiers Doctrine (Mapping XML + 3 custom types) | 2h | T-DDP1C-01 | 🔲 |
| T-DDP1C-03 | [DDD] | Adapter ClientIdType/CompanyNameType/ServiceLevelType pour DBAL 4 (drop requiresSQLCommentHint si présent) | 1h | T-DDP1C-02 | 🔲 |
| T-DDP1C-04 | [TEST] | Tests Unit `tests/Unit/Domain/Client/` — Entity + 3 VOs (~10 tests) | 4h | T-DDP1C-01 | 🔲 |
| T-DDP1C-05 | [DOC] | Doc ADR-0004 — coexistence Entity flat (`App\Entity\Client`) vs DDD Entity (`App\Domain\Client\Entity\Client`) avec règles d'usage transitoire | 1.5h | T-DDP1C-01 | 🔲 |
| T-DDP1C-06 | [REV] | Self-review + CS-Fixer + Deptrac (Domain n'autorise rien) | 1.5h | T-DDP1C-04, T-DDP1C-05 | 🔲 |

## Acceptance Criteria

- [ ] 7 fichiers `src/Domain/Client/` créés à l'identique du tag proto
- [ ] 4 fichiers Doctrine adaptés DBAL 4
- [ ] PHPStan max passe sur src/Domain/Client/
- [ ] Tests Unit ≥10 tests, 0 régression suite globale
- [ ] Deptrac valide (Domain → no deps externes)
- [ ] ADR-0004 documenté
- [ ] CompanyOwnedInterface NOT implementé sur DDD Client (pas requis Phase 1)

## Risques

| Risque | Mitigation |
|---|---|
| Conflit nom de classe avec `App\Entity\Client` | OK car namespace différent (`App\Domain\Client\Entity\Client`) |
| Doctrine confus entre 2 entities Client | Mapping XML séparé pour DDD; Entity flat reste annotations |
| ServiceLevel VO duplique enum existant | Vérifier `App\Enum\` avant cherry-pick; si conflit, ne cherry-pick que les nouveaux |

## Sortie

Branche: `feat/ddd-phase1-client`
PR cible: base main, 1 PR atomique
