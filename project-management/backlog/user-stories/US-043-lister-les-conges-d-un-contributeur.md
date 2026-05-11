# US-043 — Lister les congés d'un contributeur

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `GetContributorVacationsQuery`.

- **Implements**: FR-VAC-06 — **Persona**: P-001, P-003 — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** intervenant ou manager
**I want** lister les congés d'un contributeur
**So that** je vois l'historique et le futur.

### Acceptance Criteria
```
When GET /vacations?contributor=X
Then liste paginée avec status, type, dates
```

---

