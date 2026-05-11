# US-042 — Compter les jours approuvés

> **BC**: VAC  |  **Source**: archived VAC.md (split 2026-05-11)

> INFERRED from `CountApprovedDaysQuery`.

- **Implements**: FR-VAC-05 — **Persona**: P-001, P-003, P-004 — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** intervenant, manager ou compta
**I want** connaître le nombre de jours de congé approuvés (sur l'année / période)
**So that** je suis le solde et je facture proprement.

### Acceptance Criteria
```
Given vacations multiples
When GET /vacations/count?contributor=X&period=2026
Then nombre exact (incluant fractions DailyHours)
```

---

