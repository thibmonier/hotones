# US-016 — Composer un devis structuré

> **BC**: ORD  |  **Source**: archived ORD.md (split 2026-05-11)

> INFERRED from `OrderSection`, `OrderLine`, `OrderTask`.

- **Implements**: FR-ORD-02
- **Persona**: P-002, P-005
- **Estimate**: 8 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** composer un devis avec sections, lignes (jours × TJM) et tâches détaillées
**So that** le devis est lisible côté client et exploitable côté delivery.

### Acceptance Criteria
```
Given devis vide
When POST /orders/{id}/add-section
Then section créée
```
```
Given section
When add-line avec quantité × TJM
Then total recalculé (HT, TVA, TTC selon pays — cf. FR i18n)
```
```
Given section avec tâches
When génère le projet (au passage SIGNED)
Then ProjectTask créés à partir des OrderTask
```

### Technical Notes
- Routes existantes: `/add-line`, `/add-section`
- Calcul TVA dépend du pays (FR-CRM si VAT scope multi-pays — à valider)

---

