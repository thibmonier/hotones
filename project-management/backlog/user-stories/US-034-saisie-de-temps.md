# US-034 — Saisie de temps

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

> INFERRED from `Timesheet`, `TimesheetController`, `Service/Timesheet/*`.

- **Implements**: FR-TIM-01 — **Persona**: P-001 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** intervenant
**I want** saisir mon temps quotidien/hebdo par projet/tâche
**So that** mes heures sont facturables et la rentabilité calculée.

### Acceptance Criteria
```
Given intervenant sur projet
When POST /timesheets {date, projet, tâche, durée}
Then Timesheet créé statut "draft"
```
```
Given total > 24h sur un jour
Then refusé
```
```
Given week-end
Then accepté avec warning (heures sup)
```

---

