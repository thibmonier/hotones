# US-037 — Détection timesheet hebdo manquant

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

> INFERRED from `NotificationType::TIMESHEET_MISSING_WEEKLY`.

- **Implements**: FR-TIM-04 — **Persona**: P-001, P-002 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** intervenant (et son manager)
**I want** un rappel automatique si je n'ai pas saisi en fin de semaine
**So that** la collecte de temps est exhaustive.

### Acceptance Criteria
```
Given intervenant actif
When vendredi soir + temps hebdo < seuil minimal
Then notification TIMESHEET_MISSING_WEEKLY
```
```
Given intervenant en congé approuvé
Then pas de rappel
```

### Technical Notes
- Job scheduler (`Schedule.php`)
- Tient compte des congés (FR-VAC)

---

