# US-060 — Surveillance KPI avec seuils

> **BC**: AN  |  **Source**: archived AN.md (split 2026-05-11)

> INFERRED from `KpiThresholdExceededEvent` + `NotificationType::KPI_THRESHOLD_EXCEEDED`.

- **Implements**: FR-AN-01 — **Persona**: P-003, P-004, P-005 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** manager / compta / admin
**I want** définir des seuils sur des KPI (marge, win-rate, retard paiement, etc.) et être alerté en cas de dépassement
**So that** je détecte et corrige tôt.

### Acceptance Criteria
```
Given KPI avec seuil
When valeur observée franchit seuil
Then KpiThresholdExceededEvent + notification KPI_THRESHOLD_EXCEEDED
```
```
Given franchissement déjà notifié dans la fenêtre
Then pas de spam (debounce)
```

---

