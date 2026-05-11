# US-039 — Approuver un congé

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `ApproveVacationCommand` + `VacationApproved` event.

- **Implements**: FR-VAC-02 — **Persona**: P-003 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** manager
**I want** approuver une demande de congé en attente
**So that** l'intervenant a confirmation et le planning se verrouille.

### Acceptance Criteria
```
Given vacation REQUESTED
When manager POST /vacations/{id}/approve
Then VacationStatus = APPROVED + VacationApproved event
And notification à l'intervenant
And planning chevauchant remis en cause si nécessaire
```
```
Given vacation déjà APPROVED ou REJECTED
Then InvalidStatusTransitionException
```

---

