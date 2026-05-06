# ADR-0005 — DDD Client BC: coexistence avec Entity flat

**Status**: Accepted
**Date**: 2026-05-06
**Author**: Claude Opus 4.7 (1M context)
**Sprint**: sprint-008 — DDD-PHASE1-CLIENT (T-DDP1C-05)

---

## Context

Sprint-008 amorce l'EPIC-001 Phase 1 (cherry-pick BCs DDD additifs). Le BC `Client` est le premier livré, depuis le tag `proto/ddd-baseline-2026-01-19` (cf ADR DDD-PHASE0-001 PR #121).

L'application HotOnes possède déjà:
- Une **Entity flat** `App\Entity\Client` (Doctrine annotations, ~250 lignes, utilisée partout dans le code legacy)
- Un **Repository flat** `App\Repository\ClientRepository`
- Des **Controllers** `ClientController`, `ClientContactController`
- Des **fixtures**, factories, ApiResource attributes

Cette PR ajoute en parallèle:
- Une **DDD Entity** `App\Domain\Client\Entity\Client` (final, AggregateRootInterface, RecordsDomainEvents trait)
- 3 **Value Objects** (`ClientId`, `CompanyName`, `ServiceLevel`)
- Un **Domain Event** `ClientCreatedEvent`
- Une **Domain Exception** `ClientNotFoundException`
- Un **Repository interface** `ClientRepositoryInterface`
- Un **Mapping XML** + 3 Doctrine custom types

## Decision

**Coexistence transitoire** des 2 modèles pendant les sprints 008 → ~012.

### Règles d'usage

| Cas d'usage | Modèle à utiliser |
|---|---|
| Code legacy déjà en place (Controllers, services existants) | `App\Entity\Client` (flat, ne pas migrer immédiatement) |
| **Nouveau code** (use cases, services, controllers) | `App\Domain\Client\Entity\Client` (DDD) |
| Tests Unit nouvelle logique métier client | DDD (faster, no DB needed) |
| Tests Integration repository legacy | flat (pour ne pas régresser) |
| Doctrine mapping | DDD = XML (`Mapping/Client/Client.orm.xml`), flat = annotations |

### Mapping Doctrine: comment éviter les conflits

Les deux entités utilisent des **tables différentes**:
- `App\Entity\Client` → table `clients` (existante)
- `App\Domain\Client\Entity\Client` → table `ddd_clients` (configurée dans `Mapping/Client/Client.orm.xml`)

⚠️ Le mapping XML proto utilise par défaut le nom de table `client` (singulier) qui pourrait collisionner. **Cette PR force le nom `ddd_clients`** dans le mapping XML pour éviter le conflit.

> **Note futur sprint-009+**: une migration Phase 2 (strangler fig) consolide vers `clients` quand l'Entity flat est désactivée.

### Divergence ServiceLevel

L'Entity flat utilise les niveaux `VIP / Prioritaire / Standard / Basse priorité` (4 cases).
Le DDD utilise `STANDARD / PREMIUM / ENTERPRISE` (3 cases).

**Décision**: divergence ASSUMÉE pendant la coexistence. Phase 2 alignera (probable adoption du modèle DDD avec mapping vers les 4 valeurs flat).

## Consequences

**Positives**:
- Code legacy continue de fonctionner sans modification
- Nouveau code peut bénéficier du modèle DDD (immutability, domain events, type safety via VOs)
- Migration progressive possible (strangler fig pattern)
- Tests rapides côté DDD (no DB)

**Négatives**:
- Duplication de code: 2 entities Client + 2 sets de tests
- Confusion possible pour développeurs (quel Client utiliser ?)
- Doctrine schema drift: 2 tables (`clients` + `ddd_clients`) jusqu'à consolidation Phase 2

**Mitigation**:
- Cette ADR documente clairement les règles d'usage
- Code review prio: rejeter usage de `App\Entity\Client` dans nouveau code (lien ADR-0005)
- Tracking sprint-009+ pour Phase 2 strangler fig

## Roadmap

| Phase | Sprint cible | Action |
|---|---|---|
| **Phase 1 - Coexistence** | 008-009 (ICI) | Ajout DDD côté à côté |
| **Phase 2 - Strangler fig** | 009-011 | Nouveau code uniquement DDD |
| **Phase 3 - Migration controllers** | 010-012 | Migration progressive controllers ClientController vers use cases DDD |
| **Phase 4 - Décommissionnement** | 012-013 | Suppression Entity flat, consolidation table `clients` |

## References

- **PR #121** ADR DDD-PHASE0-001 audit branche prototype
- **PR #122** DDD-PHASE0-002 Shared kernel cherry-pick
- **EPIC-001** Migration Clean Architecture + DDD
- **gap-analysis** GAP-C5 BC stub vide → résolu Client par cette PR
- **proto baseline** tag `proto/ddd-baseline-2026-01-19`

---

**Approved**: branche `feat/ddd-phase1-client`, PR #131.
