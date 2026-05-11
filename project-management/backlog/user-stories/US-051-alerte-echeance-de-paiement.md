# US-051 — Alerte échéance de paiement

> **BC**: INV  |  **Source**: archived INV.md (split 2026-05-11)

> INFERRED from `PaymentDueAlertEvent` + `NotificationType::PAYMENT_DUE_ALERT`.

- **Implements**: FR-INV-06 — **Persona**: P-004 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** comptabilité
**I want** être alerté avant et après chaque échéance
**So that** je relance et j'évite les retards.

### Acceptance Criteria
```
Given facture due dans J-N
Then PaymentDueAlertEvent + notification PAYMENT_DUE_ALERT
```
```
Given facture en retard
Then escalade (manager + admin)
```

---
