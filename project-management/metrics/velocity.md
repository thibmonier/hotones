# Vélocité — historique sprints

Métriques par sprint pour calibrer la capacité projetée et les coefficients
par nature de story (cf. [Capacity planning](../README.md#capacity-planning-par-nature-de-story-ops-016)).

## Vélocité brute

| Sprint | Pts engagés | Pts livrés | Taux | Note |
|---:|---:|---:|---:|---|
| 003 | ~28 | ~28 | ~100% | DDD migration + tests |
| 004 | 30 | 30 | 100% | DEPS livrés en avance J1 |
| 005 | 26 | 26 | 100% | Sprint le plus efficient (~1 jour réel) |
| 006 | 22 | en cours | — | Premier sprint avec coefficients par nature |

**Moyenne 3 derniers sprints livrés** : 28 pts.

## Mix par nature (sprint-006 baseline)

| Nature | Pts | % | Coef | Pondéré |
|---|---:|---:|---:|---:|
| refactor | 8 | 36% | 1.0 | 8.0 |
| test | 8 | 36% | 0.8 | 6.4 |
| infra | 2 | 9% | 0.7 | 1.4 |
| doc-only | 4 | 18% | 1.5 | 6.0 |

Capacité projetée sprint-006 = 32 × 0.99 ≈ **31.7 pts**. Engagé 22 → marge 30%.

## Recalibrage

À mettre à jour à chaque retro :

- Si livraison > 125% du projeté pour une nature → **augmenter** son coefficient.
- Si livraison < 75% du projeté pour une nature → **diminuer** son coefficient.
- Tracer chaque ajustement avec date + raison.

### Historique des ajustements

| Date | Nature | Coef avant | Coef après | Raison |
|---|---|---:|---:|---|
| 2026-05-03 | (tous) | n/a | initial | Calibration baseline sprints 004 + 005, retro sprint-005 action #1 (OPS-016). |

## Burndown sprint courant

Voir [`sprint-006-test-debt-cleanup/task-board.md`](../sprints/sprint-006-test-debt-cleanup/task-board.md) pour le détail tâches.
