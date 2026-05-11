# US-058 — Tableau de bord RH

> **BC**: HR  |  **Source**: archived HR.md (split 2026-05-11)

> INFERRED from `HrDashboardController`.

- **Implements**: FR-HR-07 — **Persona**: P-003 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager
**I want** un dashboard RH (effectifs, satisfaction, NPS, congés posés, niveaux)
**So that** je pilote la BU.

### Acceptance Criteria
```
When GET /hr-dashboard
Then KPI agrégés tenant-scoped
```

---

