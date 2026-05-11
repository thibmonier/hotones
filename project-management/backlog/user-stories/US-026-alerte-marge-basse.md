# US-026 — Alerte marge basse

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from `LowMarginAlertEvent` + `NotificationType::LOW_MARGIN_ALERT`.

- **Implements**: FR-PRJ-07
- **Persona**: P-002, P-003, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** manager / admin
**I want** être alerté quand la marge d'un projet descend sous le seuil
**So that** je réagis avant la perte.

### Acceptance Criteria
```
Given projet avec marge calculée = (CA − CJM × jours)
When marge % < seuil tenant
Then LowMarginAlertEvent + notification LOW_MARGIN_ALERT
```
```
Given projet en perte sèche (marge < 0)
Then escalade automatique (manager + admin)
```

---

