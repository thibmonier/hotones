# US-059 — Gamification (badges, XP, leaderboard)

> **BC**: HR  |  **Source**: archived HR.md (split 2026-05-11)

> INFERRED from `Achievement`, `Badge`, `XpHistory`, `BadgeController`, `LeaderboardController`.

- **Implements**: FR-HR-08 — **Persona**: P-001 — **Estimate**: 5 pts — **MoSCoW**: Could

### Card
**As** intervenant
**I want** gagner des badges/XP en réalisant des actions clés (saisie temps complète, projets terminés)
**So that** je suis engagé dans l'usage de la plateforme.

### Acceptance Criteria
```
Given action déclenchant un Achievement
When event consommé
Then Badge attribué + XpHistory + notification
```
```
When GET /leaderboard
Then top contributeurs par XP (tenant scoped, opt-in)
```

### Technical Notes
- Considérer impact RGPD (opt-in classement public)

---
