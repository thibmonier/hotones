# US-061 — Dashboards multi-roles

> **BC**: AN  |  **Source**: archived AN.md (split 2026-05-11)

> INFERRED from `SalesDashboardController`, `HrDashboardController`, `StaffingDashboardController`, `TreasuryController`, `ProjectHealthController`, `BackofficeDashboardController`.

- **Implements**: FR-AN-02 — **Persona**: P-003..P-005 — **Estimate**: 8 pts — **MoSCoW**: Must

### Card
**As** manager / admin / compta
**I want** consulter le dashboard correspondant à mon rôle (Sales, HR, Staffing, Treasury, Project health, Backoffice)
**So that** j'ai une vue d'ensemble pertinente.

### Acceptance Criteria
```
Given user ROLE_X
When GET /<dashboard>
Then données tenant-scoped, filtres période
```
```
Given KPI lourd
Then cache Redis 5-15 min
```

---

