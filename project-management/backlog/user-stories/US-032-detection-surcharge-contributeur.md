# US-032 — Détection surcharge contributeur

> **BC**: PLN  |  **Source**: archived PLN.md (split 2026-05-11)

> INFERRED from `ContributorOverloadAlertEvent` + `NotificationType::CONTRIBUTOR_OVERLOAD_ALERT`.

- **Implements**: FR-PLN-04 — **Persona**: P-001, P-002, P-003 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** manager (et l'intervenant lui-même)
**I want** être alerté qu'un contributeur dépasse sa capacité hebdomadaire
**So that** on rééquilibre avant épuisement.

### Acceptance Criteria
```
Given heures planifiées > 100% capacité hebdo
When recalcul (event)
Then ContributorOverloadAlertEvent + notification CONTRIBUTOR_OVERLOAD_ALERT
```
```
Given surcharge persistante > N semaines
Then escalade au manager
```

---

