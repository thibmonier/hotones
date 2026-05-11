# US-068 — 11 types de notification couvrant les événements clés

> **BC**: NTF  |  **Source**: archived NTF.md (split 2026-05-11)

> INFERRED from `NotificationType` enum (11 cases).

- **Implements**: FR-NTF-04 — **Persona**: tous authentifiés — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** modéliser 11 types de notifications (QUOTE_TO_SIGN, QUOTE_WON, QUOTE_LOST, PROJECT_BUDGET_ALERT, LOW_MARGIN_ALERT, CONTRIBUTOR_OVERLOAD_ALERT, TIMESHEET_PENDING_VALIDATION, PAYMENT_DUE_ALERT, KPI_THRESHOLD_EXCEEDED, TIMESHEET_MISSING_WEEKLY, VACATION_CANCELLED_BY_MANAGER)
**So that** chaque événement métier est tracé et personnalisable.

### Acceptance Criteria
```
Given chaque event business
Then mapping vers NotificationType correct
```
```
Given internationalisation
Then libellé localisé (`getLabel`) selon locale tenant
```

### Technical Notes
- Couvre §5.3, §5.4, §5.5, §5.6, §5.7, §5.8, §5.10
- Étendre la liste = enum case + traduction + handler

---
