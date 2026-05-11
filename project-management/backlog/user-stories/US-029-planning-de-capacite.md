# US-029 — Planning de capacité

> **BC**: PLN  |  **Source**: archived PLN.md (split 2026-05-11)

> INFERRED from `Planning`, `PlanningSkill`, `PlanningController`, `PlanningOptimizationController`.

- **Implements**: FR-PLN-01 — **Persona**: P-002, P-003 — **Estimate**: 8 pts — **MoSCoW**: Must

### Card
**As** chef de projet ou manager
**I want** affecter des contributeurs à des projets sur des plages de dates
**So that** je sécurise la livraison et lisse la charge.

### Acceptance Criteria
```
Given contributeur disponible et projet actif
When POST /planning {contributor, project, dates, daily_hours}
Then Planning créé; conflits potentiels remontés
```
```
Given optimisation
When POST /planning/optimize
Then propositions d'allocation tenant compte des skills (PlanningSkill)
```
```
Given contributeur en congé approuvé chevauchant
Then refus avec explication (voir FR-VAC-02)
```

---

