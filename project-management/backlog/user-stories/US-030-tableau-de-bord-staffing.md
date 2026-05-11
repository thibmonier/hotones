# US-030 — Tableau de bord staffing

> **BC**: PLN  |  **Source**: archived PLN.md (split 2026-05-11)

> INFERRED from `StaffingDashboardController`.

- **Implements**: FR-PLN-02 — **Persona**: P-003 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager
**I want** voir le staffing global (qui est sur quoi, dispo prochaine)
**So that** j'arbitre les priorités.

### Acceptance Criteria
```
When GET /staffing
Then matrice contributeurs × semaines avec taux d'occupation
```

---

