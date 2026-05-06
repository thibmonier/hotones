# DDD-PHASE1-PROJECT — Tasks

> Cherry-pick BC Project (3 pts). 6 tasks / ~12h.

## Story rappel

Resoudre **GAP-C5** (BC stub vide pour Project). Cherry-pick depuis tag `proto/ddd-baseline-2026-01-19`. BC stub présent dans main (mais vide), pattern strangler fig.

## Files attendus

```
src/Domain/Project/
├── Entity/Project.php (302 lignes)
├── Event/ProjectCreatedEvent.php
├── Event/ProjectStatusChangedEvent.php
├── Exception/InvalidProjectStatusTransitionException.php
├── Exception/ProjectNotFoundException.php
├── Repository/ProjectRepositoryInterface.php (89 lignes)
├── ValueObject/ProjectId.php
├── ValueObject/ProjectStatus.php
└── ValueObject/ProjectType.php
```

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-DDP1P-01 | [DDD] | Vérifier état actuel `src/Domain/Project/` stub (was-cleaned in housekeeping-001 ?) | 0.5h | - | 🔲 |
| T-DDP1P-02 | [DDD] | Cherry-pick 9 fichiers Domain depuis tag proto. Conflit potentiel avec stub à résoudre | 3h | T-DDP1P-01 | 🔲 |
| T-DDP1P-03 | [DDD] | Adapter Entity Project pour status transitions invariants. Vérifier compat avec `App\Entity\Project` flat (status enum identique?) | 2h | T-DDP1P-02 | 🔲 |
| T-DDP1P-04 | [TEST] | Tests Unit Domain/Project/ — Entity + ValueObjects + status transitions (~15 tests) | 4h | T-DDP1P-02 | 🔲 |
| T-DDP1P-05 | [DOC] | Update ADR-0004 ou créer ADR-0005 — règles coexistence Entity flat ↔ DDD Project | 1h | T-DDP1P-04 | 🔲 |
| T-DDP1P-06 | [REV] | Self-review + Deptrac + tests régression suite globale | 1.5h | T-DDP1P-04, T-DDP1P-05 | 🔲 |

## Acceptance Criteria

- [ ] 9 fichiers Domain en place
- [ ] 0 collision avec Entity flat (namespace séparé)
- [ ] PHPStan max + Deptrac passent
- [ ] ProjectStatus enum DDD aligné avec enum flat existant (ou ADR explique divergence)
- [ ] ≥15 tests Unit, 0 régression suite globale
- [ ] ADR-0005 documente règles coexistence

## Risques

| Risque | Mitigation |
|---|---|
| ProjectStatus DDD ≠ ProjectStatus flat (incohérence métier) | Audit T-DDP1P-01 valide ou divergence documentée ADR |
| Project entity flat utilise Doctrine annotations + DDD utilise XML | OK, séparation par namespace |
| Stub Project namespace conflict (housekeeping-001 PR avait nettoyé) | T-DDP1P-01 audit décisif |

## Sortie

Branche: `feat/ddd-phase1-project`. PR base main.
