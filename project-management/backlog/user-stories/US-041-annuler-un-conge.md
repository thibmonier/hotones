# US-041 — Annuler un congé

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `CancelVacationCommand` + `VacationCancelled` + `NotificationType::VACATION_CANCELLED_BY_MANAGER`.

- **Implements**: FR-VAC-04 — **Persona**: P-001, P-003 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** intervenant ou manager
**I want** annuler un congé (avant ou pendant)
**So that** je gère un imprévu.

### Acceptance Criteria
```
Given APPROVED non commencé
When intervenant POST /vacations/{id}/cancel
Then CANCELLED + VacationCancelled event
```
```
Given manager annule un congé approuvé
Then notification VACATION_CANCELLED_BY_MANAGER + raison
```
```
Given vacation déjà CANCELLED
Then InvalidStatusTransitionException
```

---

