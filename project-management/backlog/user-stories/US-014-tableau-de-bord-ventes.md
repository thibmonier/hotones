# US-014 — Tableau de bord ventes

> **BC**: CRM  |  **Source**: archived CRM.md (split 2026-05-11)

> INFERRED from `SalesDashboardController` + `FactForecast`.

- **Implements**: FR-CRM-05
- **Persona**: P-003, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** manager / admin
**I want** voir un dashboard ventes (pipeline, win-rate, prévisions)
**So that** je pilote mon activité commerciale.

### Acceptance Criteria
```
Given user ROLE_MANAGER+
When GET /sales-dashboard
Then voit: CA signé, CA en pipeline, win-rate, top clients, prévisions trimestre
```
```
Given multi-tenant
Then données scoped à la société courante
```
```
When période modifiée (filtres)
Then KPI recalculés
```

### Technical Notes
- Cache Redis pour KPI lourds
- Forecast ML/statistique (FactForecast) — algo à expliciter

---
