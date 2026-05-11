# US-027 — Liste des projets à risque

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from route `/at-risk`.

- **Implements**: FR-PRJ-08
- **Persona**: P-003, P-005
- **Estimate**: 3 pts
- **MoSCoW**: Should

### Card
**As** manager / admin
**I want** une vue unique des projets "à risque" (santé basse, budget dépassé, marge faible)
**So that** je priorise mes interventions.

### Acceptance Criteria
```
Given multiples projets
When GET /at-risk
Then liste filtrée triée par sévérité
```

---

