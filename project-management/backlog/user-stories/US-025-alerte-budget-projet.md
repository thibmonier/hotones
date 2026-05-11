# US-025 — Alerte budget projet

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from `ProjectBudgetAlertEvent` + `NotificationType::PROJECT_BUDGET_ALERT`.

- **Implements**: FR-PRJ-06
- **Persona**: P-002, P-003
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** être alerté quand un projet approche/dépasse son budget
**So that** je négocie un avenant ou ralentis la consommation.

### Acceptance Criteria
```
Given projet avec budget = 100 jours-homme
When jours consommés ≥ 80% (seuil paramétrable)
Then ProjectBudgetAlertEvent
And notification PROJECT_BUDGET_ALERT
```
```
When dépassement > 100%
Then notification urgente (canal email + in-app)
```

---

