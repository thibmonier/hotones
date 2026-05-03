# Sprints — index

Vue d'ensemble des sprints du projet HotOnes. Format Scrum (2 semaines fixes,
~32 pts capacité brute, recalibrée par nature de story depuis sprint-006 —
voir [Capacity planning](../README.md#capacity-planning-par-nature-de-story-ops-016)).

## Sprints livrés

| Sprint | Goal | Pts engagés | Pts livrés | % | Notes |
|---:|---|---:|---:|---:|---|
| [001](sprint-001-walking-skeleton/) | Walking skeleton — auth + parcours minimal | — | — | — | Base technique posée |
| [002](sprint-002-tests_consolidation/) | Tests consolidation + gap-analysis Critical (TEST-001..005) | — | — | — | 11 gaps Critical → 4 absorbés |
| [003](sprint-003-stabilization/) | Stabilization — DDD migration Vacation + tech debt | — | — | — | DEPS-001/2/3 livrés en avance J1 |
| [004](sprint-004-quality-foundation/) | 7 gaps Critical résiduels + dette infra (PR stack, smoke staging, deps) | 30 | 30 | 100% | OPS-010 retiré (cf. REFACTOR-002) |
| [005](sprint-005-test-stabilization/) | Tests Vacation fonctionnels + dette mockObjects + pre-push hook fiable | 26 | 26 | 100% | Sprint le plus efficient — ~1 jour réel vs 10 planifiés |

## Sprint en cours

| Sprint | Goal | Pts engagés | Pts livrés (J0) | Statut |
|---:|---|---:|---:|---|
| [006](sprint-006-test-debt-cleanup/) | Dette `AllowMockObjectsWithoutExpectations` (31 classes) + audit `skip-pre-push` (14 classes) + guards workflow | 22 | en cours | Premier sprint avec coefficients capacité par nature |

## Conventions

- **Nommage** : `sprint-NNN-but` (ex: `sprint-006-test-debt-cleanup`).
- **Documents par sprint** :
  - `sprint-goal.md` : objectif + backlog + dépendances + risques.
  - `tasks/` : décomposition par story + task-board.
  - `status-YYYY-MM-DD.md` : snapshots ponctuels (kickoff, mi-sprint).
  - `sprint-review.md` : à la fin, métriques + démo + impact backlog.
  - `sprint-retro.md` : à la fin, format Starfish + actions SMART.

## Cérémonies

| Cérémonie | Durée | Récurrence |
|---|---:|---|
| Sprint Planning Part 1 (QUOI) | 2h | J1 09:00 |
| Sprint Planning Part 2 (COMMENT) | 2h | J1 14:00 |
| Daily Scrum | 15 min | quotidien 09:30 |
| Affinage Backlog (sprint suivant) | 1h | J9 14:00 |
| Sprint Review | 2h | J10 14:00 |
| Rétrospective | 1h30 | J10 16:30 |

## Vélocité — historique

| Sprint | Pts | Mix dominant |
|---:|---:|---|
| 003 | ~28 | DDD + tests + ops |
| 004 | 30 | gaps tests + ops + deps |
| 005 | 26 | refactor + tests + doc |
| 006 | 22 (engagé) | refactor + test + doc |

Vélocité moyenne 3 derniers sprints livrés : **28 pts**. Recalibrage projeté
sprint-006 via coefficients par nature : ~31.7 pts (engagé volontairement à 22).
