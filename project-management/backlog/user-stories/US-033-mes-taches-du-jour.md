# US-033 — Mes tâches du jour

> **BC**: PLN  |  **Source**: archived PLN.md (split 2026-05-11)

> INFERRED from `MyTasksController`.

- **Implements**: FR-PLN-05 — **Persona**: P-001, P-002 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** intervenant ou chef de projet
**I want** voir mes tâches actives du jour/semaine
**So that** je sais quoi faire en premier.

### Acceptance Criteria
```
When GET /my-tasks
Then liste tâches assignées (ProjectTask + ProjectSubTask) triées par priorité/échéance
```

---
