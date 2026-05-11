# US-052 — Profil contributeur + skills/techno

> **BC**: HR  |  **Source**: archived HR.md (split 2026-05-11)

> INFERRED from `Contributor`, `ContributorSkill`, `ContributorTechnology`.

- **Implements**: FR-HR-01 — **Persona**: P-001, P-003 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** intervenant ou manager
**I want** maintenir le profil contributeur (skills, technologies, niveau)
**So that** le staffing et la GPEC sont à jour.

### Acceptance Criteria
```
Given intervenant
When POST /contributor-skills / /contributor-technologies
Then liens persistés avec niveau (junior/senior/expert)
```

---

