# Module: Invoicing & Treasury

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.8 (FR-INV-01..06). Generated 2026-05-04.

---

## US-046 — CRUD facture

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

## US-047 — Facturation projet

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

## US-048 — Marqueurs de facturation (jalons)

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

## US-049 — Notes de frais

> INFERRED from `ExpenseReport`, `ExpenseReportController`.

- **Implements**: FR-INV-04 — **Persona**: P-001, P-004 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** intervenant
**I want** soumettre une note de frais (justificatif, projet imputé)
**So that** je suis remboursé et le coût projet est exact.

### Acceptance Criteria
```
When POST /expense-reports avec PJ (S3)
Then ExpenseReport créé statut "draft"
```
```
Given soumission
Then routée à manager + compta pour validation
```
```
Given approuvée
Then imputée au projet (impact CJM ajusté)
```

---

## US-050 — Tableau de bord trésorerie

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

## US-051 — Alerte échéance de paiement

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

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-046 | CRUD facture | FR-INV-01 | 5 | Must |
| US-047 | Facturation projet | FR-INV-02 | 5 | Must |
| US-048 | Marqueurs facturation | FR-INV-03 | 3 | Should |
| US-049 | Notes de frais | FR-INV-04 | 5 | Should |
| US-050 | Dashboard trésorerie | FR-INV-05 | 5 | Should |
| US-051 | Alerte paiement | FR-INV-06 | 3 | Must |
| **Total** | | | **26** | |
