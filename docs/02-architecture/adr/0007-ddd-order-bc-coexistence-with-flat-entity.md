# ADR-0007 — DDD Order BC: coexistence avec Entity flat

**Status**: Accepted
**Date**: 2026-05-06
**Author**: Claude Opus 4.7 (1M context)
**Sprint**: sprint-008 — DDD-PHASE1-ORDER (T-DDP1O-06)

---

## Context

Sprint-008 cherry-pick le BC DDD `Order` depuis le tag `proto/ddd-baseline-2026-01-19`. Plus complexe que Client et Project: 14 fichiers, 3 entities (Order + OrderLine + OrderSection), 6 ValueObjects, 2 Events.

L'application HotOnes possède déjà:
- Entity flat `App\Entity\Order` (~1000+ lignes, ApiResource attributes, 7 statuts)
- `App\Entity\OrderLine`, `App\Entity\OrderSection` (entités enfants)
- `App\Enum\OrderStatus` (7 valeurs: a_signer/gagne/signe/perdu/termine/standby/abandonne)

DDD apporte:
- 3 entities équivalentes (`App\Domain\Order\Entity\{Order,OrderLine,OrderSection}`)
- 6 VOs (`OrderId`, `OrderLineId`, `OrderSectionId`, `OrderStatus`, `OrderLineType`, `ContractType`)
- 2 Events + 2 Exceptions
- **State machine OrderStatus** (canTransitionTo) — anti-corruption majeure

## Decision

Suivre le **même pattern de coexistence** que ADR-0005 (Client) et ADR-0006 (Project).

### Divergences notables vs Entity flat

#### OrderStatus — superset (8 cases vs 7)

| Status | Flat | DDD |
|---|:-:|:-:|
| draft | ❌ | ✅ |
| a_signer | ✅ | ✅ |
| gagne | ✅ | ✅ |
| signe | ✅ | ✅ |
| perdu | ✅ | ✅ |
| termine | ✅ | ✅ |
| standby | ✅ | ✅ |
| abandonne | ✅ | ✅ |

DDD ajoute `DRAFT`. Sémantique métier:
- DRAFT = en cours de saisie commerciale, pas encore prêt à signer
- TO_SIGN = prêt à présenter au client

#### State machine

L'Entity flat utilise `OrderStatus::STATUS_OPTIONS` (mapping label) mais **n'impose aucune transition**. Tout statut peut passer à tout autre.

Le DDD impose:
- `DRAFT` → [TO_SIGN, ABANDONED]
- `TO_SIGN` → [WON, LOST, STANDBY, ABANDONED]
- `WON` → [SIGNED, LOST, STANDBY]
- `SIGNED` → [COMPLETED, STANDBY, ABANDONED]
- `STANDBY` → [TO_SIGN, WON, SIGNED, ABANDONED]
- `LOST/COMPLETED/ABANDONED` → terminaux

→ **Forte amélioration métier** (anti-corruption). Permet de tracer un funnel de signature précis (DRAFT → TO_SIGN → WON → SIGNED → COMPLETED).

#### winProbability (Q5 atelier business)

Le PR #130 (DB-MIG-ATELIER) a ajouté `Order.winProbability` sur l'Entity flat. Le DDD prototype **n'inclut PAS ce champ**.

**Décision Phase 1**: garder `winProbability` exclusivement sur Entity flat. Phase 2 décidera du mapping (probable: champ commercial accessible via use case DDD aussi).

#### OrderLine + OrderSection

DDD apporte une vraie hiérarchie:
- `Order` aggregate root
- `OrderSection` enfant agrégé par Order
- `OrderLine` enfant agrégé par OrderSection

Avec invariants métier (sum des lines = section total, sum des sections = order total). Tests unit valident ces invariants.

L'Entity flat a la même structure mais via Doctrine relations bi-directionnelles.

### Doctrine mapping

PAS de mapping XML cette PR (cohérent avec ADR-0006). Phase 2 ajoutera mapping + migration vers DDD.

## Consequences

**Positives**:
- State machine OrderStatus = anti-corruption forte
- Nouveau statut DRAFT permet de mieux tracer le funnel commercial
- 3 entities + invariants métier = modèle riche pour future Phase 2
- 2 Domain Events disponibles (OrderCreatedEvent, OrderStatusChangedEvent)

**Négatives**:
- Cohérence à maintenir entre 2 modèles parallèles
- Coût d'apprentissage pour développeurs

**Mitigation**:
- ADR documente clairement
- Tests garantissent invariants (19 tests Order Entity)
- Phase 2 alignera

## Roadmap consolidée Phase 1 → Phase 4

| Phase | Sprint cible | Action |
|---|---|---|
| **Phase 1 - Coexistence** | 008 (ICI) | 3 BCs DDD ajoutés (Client/Project/Order) |
| **Phase 2 - Strangler fig** | 009-011 | Use cases nouveau code DDD; bridge anti-corruption layer ↔ Entity flat |
| **Phase 3 - Migration controllers** | 011-013 | Controllers progressivement migrent vers use cases DDD |
| **Phase 4 - Décommissionnement** | 013-015 | Suppression Entity flat, consolidation tables |

## References

- **ADR-0005** Client BC coexistence
- **ADR-0006** Project BC coexistence
- **PR #121** audit branche prototype
- **PR #130** DB-MIG-ATELIER (winProbability sur Entity flat)
- **EPIC-001** Migration Clean Architecture + DDD

---

**Approved**: branche `feat/ddd-phase1-order`, PR #133.
