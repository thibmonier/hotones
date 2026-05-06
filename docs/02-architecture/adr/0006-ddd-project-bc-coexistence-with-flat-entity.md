# ADR-0006 — DDD Project BC: coexistence avec Entity flat

**Status**: Accepted
**Date**: 2026-05-06
**Author**: Claude Opus 4.7 (1M context)
**Sprint**: sprint-008 — DDD-PHASE1-PROJECT (T-DDP1P-05)

---

## Context

Sprint-008 cherry-pick le BC DDD `Project` depuis le tag `proto/ddd-baseline-2026-01-19` (cf ADR DDD-PHASE0-001 PR #121). Ce BC est l'un des plus complexes du prototype: 9 fichiers, ~302 lignes Entity, state machine 5 états.

L'application HotOnes possède déjà:
- Une **Entity flat** `App\Entity\Project` (Doctrine annotations, ~1000 lignes, status `active/completed/cancelled`)
- Un **Repository flat** `App\Repository\ProjectRepository`
- Des controllers + fixtures + ApiResource attributes

Cette PR ajoute en parallèle:
- **DDD Entity** `App\Domain\Project\Entity\Project` (final, AggregateRootInterface)
- 3 **Value Objects** (`ProjectId`, `ProjectStatus`, `ProjectType`)
- 2 **Domain Events** (`ProjectCreatedEvent`, `ProjectStatusChangedEvent`)
- 2 **Domain Exceptions** (`ProjectNotFoundException`, `InvalidProjectStatusTransitionException`)
- 1 **Repository interface**

## Decision

Suivre le **même pattern de coexistence** que ADR-0005 (Client BC):

### Règles d'usage

| Cas d'usage | Modèle à utiliser |
|---|---|
| Code legacy (Controllers, services existants, ApiResource) | `App\Entity\Project` |
| Nouveau use case / service domain | `App\Domain\Project\Entity\Project` |
| Tests Unit logique métier project | DDD |

### Divergence ProjectStatus (5 cases vs 3 cases)

| Status | Entity flat | DDD Entity |
|---|:-:|:-:|
| draft | ❌ | ✅ |
| active | ✅ | ✅ |
| on_hold | ❌ | ✅ |
| completed | ✅ | ✅ |
| cancelled | ✅ | ✅ |

**DDD est un superset**. Phase 2 alignera (probable adoption du DDD avec migration de données pour les projects existants en `active` qui devraient être `draft`).

### State machine DDD

Le DDD ProjectStatus impose des **transitions explicites** (canTransitionTo):
- DRAFT → [ACTIVE, CANCELLED]
- ACTIVE → [ON_HOLD, COMPLETED, CANCELLED]
- ON_HOLD → [ACTIVE, CANCELLED]
- COMPLETED → [] (terminal)
- CANCELLED → [] (terminal)

L'Entity flat n'avait **aucune** validation transitionnelle: tout statut pouvait transiter vers tout autre. **DDD apporte une amélioration métier** importante (anti-corruption).

### Doctrine mapping

Pour cette PR, **PAS de mapping XML** (différé sprint-009+).
Raison: Phase 1 = aggregate Domain seulement. Sans mapping, l'entity DDD n'est pas persistée — elle vit en mémoire pour les use cases. Quand un use case voudra persister, il appellera l'Entity flat via un anti-corruption layer (à implémenter Phase 2).

## Consequences

**Positives**:
- State machine DDD apporte une vraie valeur métier (anti-corruption)
- Extensibilité (statuts supplémentaires faciles à ajouter Phase 2)
- Tests Unit rapides
- 2 nouveaux Domain Events disponibles (audit + integration)

**Négatives**:
- Pas de persistance immédiate (DDD entity in-memory only Phase 1)
- Divergence statuts (3 vs 5) — devra être tranchée Phase 2

**Mitigation**:
- ADR documente l'intention et les phases
- Tests garantissent invariants (24 tests)
- Sprint-009+ inclura mapping XML + migration des données

## References

- **ADR-0005** Client BC coexistence (même pattern)
- **PR #121** audit branche prototype
- **EPIC-001** Migration Clean Architecture + DDD
- **gap-analysis** GAP-C5 BC stubs vides → résolu Project par cette PR

---

**Approved**: branche `feat/ddd-phase1-project`, PR #132.
