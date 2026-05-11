# US-048 — Marqueurs de facturation (jalons)

> **BC**: INV  |  **Source**: archived INV.md (split 2026-05-11)

> INFERRED from `BillingMarker` + `BillingController`.

- **Implements**: FR-INV-03 — **Persona**: P-004 — **Estimate**: 3 pts — **MoSCoW**: Should

### Card
**As** comptabilité
**I want** poser des marqueurs (jalons facturables) sur les projets
**So that** je sais quoi facturer quand.

### Acceptance Criteria
```
When add BillingMarker {projet, date, montant, motif}
Then visible côté CP + compta
```

---

