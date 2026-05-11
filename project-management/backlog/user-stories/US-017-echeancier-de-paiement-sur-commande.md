# US-017 — Échéancier de paiement sur commande

> **BC**: ORD  |  **Source**: archived ORD.md (split 2026-05-11)

> INFERRED from `OrderPaymentSchedule`.

- **Implements**: FR-ORD-03
- **Persona**: P-004, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** comptabilité ou admin
**I want** définir un échéancier de paiement sur la commande (acompte 30%, jalons, solde)
**So that** la facturation et le suivi de trésorerie sont alignés sur les conditions négociées.

### Acceptance Criteria
```
Given commande SIGNED de 10 000 €
When je définis 30% à signature, 40% mid-projet, 30% livraison
Then 3 OrderPaymentSchedule créés avec dates prévisionnelles
And chacun déclenche une facture le moment venu (FR-INV-02)
```
```
Given somme des % ≠ 100%
When sauvegarde
Then refusée (validation)
```

---

