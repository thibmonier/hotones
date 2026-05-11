# US-047 — Facturation projet

> **BC**: INV  |  **Source**: archived INV.md (split 2026-05-11)

> INFERRED from `ProjectBillingController`.

- **Implements**: FR-INV-02 — **Persona**: P-004 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** comptabilité
**I want** facturer un projet selon son échéancier ou ses jours validés
**So that** la facturation reflète la réalité opérationnelle.

### Acceptance Criteria
```
Given projet avec OrderPaymentSchedule + Timesheets approuvés
When génération facture sur jalon
Then facture pré-remplie depuis projet
```

---

