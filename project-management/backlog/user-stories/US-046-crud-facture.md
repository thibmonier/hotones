# US-046 — CRUD facture

> **BC**: INV  |  **Source**: archived INV.md (split 2026-05-11)

> INFERRED from `Invoice`, `InvoiceLine`, `InvoiceController`.

- **Implements**: FR-INV-01 — **Persona**: P-004, P-005 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** comptabilité ou admin
**I want** créer/modifier/annuler une facture multi-lignes
**So that** je facture mes clients.

### Acceptance Criteria
```
When POST /invoices {client, lines[]}
Then Invoice créée + lignes; calcul HT/TVA/TTC
```
```
Given facture émise (statut SENT)
Then modifications interdites; avoir requis pour correction
```

### Technical Notes
- Numérotation séquentielle légale (par société, par exercice)

---

