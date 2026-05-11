# US-040 — Refuser un congé

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `RejectVacationCommand` + `VacationRejected` event.

- **Implements**: FR-VAC-03 — **Persona**: P-003 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** manager
**I want** refuser une demande de congé avec un motif
**So that** l'intervenant comprend la décision.

### Acceptance Criteria
```
Given REQUESTED
When POST /vacations/{id}/reject {reason}
Then VacationStatus = REJECTED + VacationRejected event + notification
```
```
Given motif vide
Then refusé
```

---

