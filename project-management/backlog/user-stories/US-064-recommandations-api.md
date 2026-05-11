# US-064 — Recommandations API

> **BC**: AN  |  **Source**: archived AN.md (split 2026-05-11)

> INFERRED from route `/api/recommendations`.

- **Implements**: FR-AN-05 — **Persona**: P-002, P-003 — **Estimate**: 5 pts — **MoSCoW**: Could

### Card
**As** chef de projet ou manager
**I want** un endpoint qui me suggère des actions (rééquilibrer staffing, augmenter TJM, archiver projet)
**So that** je suis guidé.

### Acceptance Criteria
```
Given GET /api/recommendations
Then liste de recommandations triées par valeur attendue
```

---
