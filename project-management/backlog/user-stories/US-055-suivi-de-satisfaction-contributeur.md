# US-055 — Suivi de satisfaction contributeur

> **BC**: HR  |  **Source**: archived HR.md (split 2026-05-11)

> INFERRED from `ContributorSatisfaction`, `ContributorSatisfactionController`.

- **Implements**: FR-HR-04 — **Persona**: P-001, P-003 — **Estimate**: 3 pts — **MoSCoW**: Should

### Card
**As** intervenant et manager
**I want** mesurer la satisfaction (auto-déclarée + observée)
**So that** je détecte les signaux faibles.

### Acceptance Criteria
```
When intervenant soumet score périodique
Then ContributorSatisfaction persisté
```
```
Given moyenne en baisse N périodes consécutives
Then alerte manager
```

---

