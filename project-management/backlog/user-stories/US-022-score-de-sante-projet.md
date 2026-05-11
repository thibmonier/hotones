# US-022 — Score de santé projet

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from `ProjectHealthScore` + `ProjectHealthController`.

- **Implements**: FR-PRJ-03
- **Persona**: P-002, P-003
- **Estimate**: 8 pts
- **MoSCoW**: Should

### Card
**As** chef de projet ou manager
**I want** un score de santé composite par projet (budget, planning, satisfaction, marge)
**So that** je détecte tôt les projets qui dérapent.

### Acceptance Criteria
```
Given projet en cours
When job batch s'exécute
Then 4 sous-scores calculés ∈ [0,100]: budgetScore, timelineScore, velocityScore, qualityScore
And composite score = 0.25 * budgetScore + 0.25 * timelineScore + 0.25 * velocityScore + 0.25 * qualityScore
And healthLevel mappé: [0,30]=critical / [30,60]=at-risk / [60,80]=healthy / [80,100]=excellent
And ProjectHealthScore persisté avec recommendations[] + details[] (JSON)
```
```
When GET /risks/projects (RiskController) ou /project-health (ProjectHealthController)
Then liste projets avec scores triés desc + healthLevel + nb à risque par catégorie
```
```
Given score franchit seuil critique (=30) à la baisse
Then KpiThresholdExceededEvent dispatché (FR-AN-01) + notification
```

### Technical Notes
- **Pondération validée (atelier 2026-05-15)**: 25/25/25/25 (V1; rebalancing futur possible).
- **Mapping healthLevel** (V1): critical < 30 ≤ at-risk < 60 ≤ healthy < 80 ≤ excellent.
- Implémentation: `ProjectHealthScore` entity + service de calcul (à isoler si dispersé).
- Test unitaire pondération composite + tests de mapping healthLevel.

---

