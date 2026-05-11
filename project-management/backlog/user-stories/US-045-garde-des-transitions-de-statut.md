# US-045 — Garde des transitions de statut

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `InvalidStatusTransitionException` + `VacationStatus` VO.

- **Implements**: FR-VAC-08 — **Persona**: système — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** rejeter toute transition de statut illégale (ex: REJECTED → APPROVED)
**So that** l'intégrité du workflow est garantie.

### Acceptance Criteria
```
Given REJECTED
When tentative APPROVED
Then InvalidStatusTransitionException
```
```
Given matrice de transitions documentée
Then test couvre tous les cas illégaux
```

### Technical Notes
- Reférence implémentation pour autres BCs (état de l'art DDD du repo)

---
