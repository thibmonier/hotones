# US-050 — Tableau de bord trésorerie

> **BC**: INV  |  **Source**: archived INV.md (split 2026-05-11)

> INFERRED from `TreasuryController`, `FactForecast`.

- **Implements**: FR-INV-05 — **Persona**: P-004, P-005 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** compta / admin
**I want** voir la trésorerie (entrées prévues, sorties, solde projeté)
**So that** je pilote le BFR.

### Acceptance Criteria
```
When GET /treasury
Then dashboard: factures en attente d'encaissement, échéances fournisseurs, prévisions 30/60/90j
```

---

