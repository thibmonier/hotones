# US-078 — Sitemap public

> **BC**: MKT  |  **Source**: archived MKT.md (split 2026-05-11)

> INFERRED from `presta/sitemap-bundle`.

- **Implements**: FR-MKT-04 — **Persona**: système, P-007 — **Estimate**: 2 pts — **MoSCoW**: Should

### Card
**As** moteur de recherche
**I want** consulter `/sitemap.xml` à jour
**So that** mon indexation est optimale.

### Acceptance Criteria
```
When GET /sitemap.xml
Then sitemap valide listant pages publiques + blog
```

---
