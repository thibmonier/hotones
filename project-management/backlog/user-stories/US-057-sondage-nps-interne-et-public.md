# US-057 — Sondage NPS interne et public

> **BC**: HR  |  **Source**: archived HR.md (split 2026-05-11)

> INFERRED from `NpsSurvey`, `NpsController`, `NpsPublicController`.

- **Implements**: FR-HR-06 — **Persona**: P-001, P-003, P-007 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager (interne) ou client (public)
**I want** collecter un NPS périodique
**So that** je mesure l'attachement.

### Acceptance Criteria
```
Given lien NPS public {token}
When client soumet score 0-10 + commentaire
Then NpsSurvey persistée anonyme/identifiée
```
```
Given NPS interne
When intervenant soumet
Then auto-anonyme côté affichage
```

---

