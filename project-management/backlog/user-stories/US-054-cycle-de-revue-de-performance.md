# US-054 — Cycle de revue de performance

> **BC**: HR  |  **Source**: archived HR.md (split 2026-05-11)

> INFERRED from `PerformanceReview`, `PerformanceReviewController`.

- **Implements**: FR-HR-03 — **Persona**: P-003 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager
**I want** lancer/planifier des revues de performance
**So that** je documente les évaluations annuelles/périodiques.

### Acceptance Criteria
```
When POST /performance-reviews {contributor, period, evaluator}
Then PerformanceReview créée statut "draft"
```
```
Given draft
When manager + intervenant complètent
Then statut "completed", verrouillée
```

---

