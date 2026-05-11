# US-036 — Validation des temps

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

> INFERRED from `TimesheetPendingValidationEvent` + `NotificationType::TIMESHEET_PENDING_VALIDATION`.

- **Implements**: FR-TIM-03 — **Persona**: P-001, P-002, P-003 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** chef de projet / manager
**I want** valider/refuser les timesheets soumis
**So that** la facturation et la rentabilité reposent sur du temps validé.

### Acceptance Criteria
```
Given intervenant soumet semaine
When POST /timesheets/{id}/submit
Then statut "pending_validation" + TimesheetPendingValidationEvent
And notification au manager
```
```
Given manager
When approve
Then statut "approved", verrouillé en modification
```
```
Given refus
Then statut "rejected" + commentaire requis
And notification à l'intervenant
```

---

