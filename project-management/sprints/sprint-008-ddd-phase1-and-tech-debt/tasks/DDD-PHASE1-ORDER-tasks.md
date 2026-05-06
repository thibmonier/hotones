# DDD-PHASE1-ORDER — Tasks

> Cherry-pick BC Order (3 pts). 7 tasks / ~14h.

## Story rappel

BC Order DDD le plus complexe du proto (3 entities + Events + Exceptions + 5+ ValueObjects). Modèle commercial avec OrderLine + OrderSection. Cherry-pick additif (existant `App\Entity\Order` est flat).

## Files attendus

```
src/Domain/Order/
├── Entity/Order.php
├── Entity/OrderLine.php
├── Entity/OrderSection.php
├── Event/*.php (multiple)
├── Exception/*.php
├── Repository/OrderRepositoryInterface.php
├── ValueObject/OrderId.php
├── ValueObject/OrderLineId.php
├── ValueObject/OrderLineType.php
├── ValueObject/OrderSectionId.php
└── ValueObject/OrderStatus.php

src/Infrastructure/Persistence/Doctrine/
├── Mapping/Order.orm.xml
├── Mapping/OrderLine.orm.xml
├── Mapping/OrderSection.orm.xml
└── Type/{OrderId,OrderLineId,OrderLineType,OrderSectionId,OrderStatus}Type.php
```

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-DDP1O-01 | [DDD] | Inventaire fichiers Order proto (compter exactement Entity + VOs + Events) | 0.5h | - | 🔲 |
| T-DDP1O-02 | [DDD] | Cherry-pick Domain layer (entities + VOs + Events + Exceptions + Repo interface) | 3h | T-DDP1O-01 | 🔲 |
| T-DDP1O-03 | [DDD] | Cherry-pick 3 mapping XML + 5 custom Doctrine types, adapt DBAL 4 | 2h | T-DDP1O-02 | 🔲 |
| T-DDP1O-04 | [DDD] | Verify Order DDD invariants (status transitions, line totals, sections sum) | 2h | T-DDP1O-02 | 🔲 |
| T-DDP1O-05 | [TEST] | Tests Unit Order Entity + OrderLine + OrderSection (~20 tests) — invariants + status transitions + line calc | 4h | T-DDP1O-04 | 🔲 |
| T-DDP1O-06 | [DOC] | ADR-0006 — Order DDD model rationale + coexistence avec Order flat (winProbability, etc.) | 1h | T-DDP1O-05 | 🔲 |
| T-DDP1O-07 | [REV] | Self-review + Deptrac + suite Unit + check Money VO interop (DDD-PHASE0-002) | 1.5h | T-DDP1O-05, T-DDP1O-06 | 🔲 |

## Acceptance Criteria

- [ ] 3 entities + 5+ VOs + Events + Exceptions cherry-pickés
- [ ] 3 Mapping XML + 5 Doctrine types DBAL 4 OK
- [ ] OrderStatus DDD enum cohérent avec OrderStatus flat existant
- [ ] Money VO du Shared kernel utilisé pour montants
- [ ] PHPStan max + Deptrac OK
- [ ] ≥20 tests Unit, 0 régression
- [ ] ADR-0006 documente choix architecturaux

## Risques

| Risque | Mitigation |
|---|---|
| Order DDD complexité > 3 pts | Time-box: si > 14h, déférer OrderSection en buffer |
| Money VO du Shared kernel ≠ format prototype | Bridge OK car proto utilise probablement même VO |
| Order flat utilise winProbability (atelier business) | Reste en flat; DDD Order Phase 1 = sous-ensemble |

## Sortie

Branche: `feat/ddd-phase1-order`. PR base main.
