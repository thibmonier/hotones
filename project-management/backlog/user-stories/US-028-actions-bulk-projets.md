# US-028 — Actions bulk projets

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from routes `/bulk-archive`, `/bulk-delete`.

- **Implements**: FR-PRJ-09
- **Persona**: P-003, P-005
- **Estimate**: 3 pts
- **MoSCoW**: Could

### Card
**As** manager / admin
**I want** archiver/supprimer plusieurs projets en une fois
**So that** je nettoie rapidement.

### Acceptance Criteria
```
Given sélection N projets
When POST /bulk-archive
Then tous archivés en une transaction
```
```
Given suppression: confirmation explicite requise (irréversible)
```

### Technical Notes
- Soft delete recommandé (Gedmo)
- Limite tenant (interdire bulk hors tenant)

---
