# US-049 — Notes de frais

> **BC**: INV  |  **Source**: archived INV.md (split 2026-05-11)

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

