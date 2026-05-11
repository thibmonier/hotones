# US-044 — Lister congés en attente pour un manager

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `GetPendingVacationsForManagerQuery`.

- **Implements**: FR-VAC-07 — **Persona**: P-003 — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** manager
**I want** voir uniquement les demandes REQUESTED qui me concernent
**So that** je traite ma file rapidement.

### Acceptance Criteria
```
Given manager + équipe rattachée
When GET /vacations/pending
Then uniquement REQUESTED des contributeurs sous ma responsabilité
```

---

