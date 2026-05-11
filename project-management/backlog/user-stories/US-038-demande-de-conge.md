# US-038 — Demande de congé

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `RequestVacationCommand` + VOs `DateRange`, `DailyHours`, `VacationStatus`, `VacationType`.

- **Implements**: FR-VAC-01 — **Persona**: P-001 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** intervenant
**I want** poser une demande de congé (type, dates, heures journalières)
**So that** mon manager peut l'approuver et mon planning est ajusté.

### Acceptance Criteria
```
Given intervenant authentifié
When POST /vacations {type, start, end, daily_hours}
Then VacationStatus = REQUESTED + VacationRequested event
And notification au manager direct
```
```
Given chevauchement avec congé existant
Then refusé (InvalidVacationException)
```
```
Given dates passées
Then refusé (sauf cas exceptionnels paramétrés)
```

### Technical Notes
- VOs `DateRange`, `DailyHours` valident les invariants
- Couvre intervenants à temps partiel (DailyHours)

---

