# US-101 — Workflow Symfony state machine `work_item` 4 états + cross-aggregate Invoice

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

- **Implements**: EPIC-003 Phase 3 — **Persona**: Tech Lead + P-002 manager — **Estimate**: 4 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** Tech Lead
**I want** un Workflow Symfony state machine `work_item` à 4 états (`draft → validated → billed → paid`) avec transitions auto déclenchées par events Invoice BC
**So that** WorkItem cycle de vie complet (saisie → validation → facturation → paiement) traçable Domain + UI affiche statut propre.

### Acceptance Criteria

```
Given config/packages/workflow.yaml configuré state machine "work_item"
When WorkItem créé
Then status initial = "draft"
```

```
Given WorkItem status "draft"
And user ROLE_MANAGER ou ROLE_ADMIN déclenche transition validate
When workflow.apply(workItem, "validate")
Then status devient "validated"
And WorkItemValidatedEvent dispatché
```

```
Given WorkItem status "draft"
And transition bill tentée (skip validated)
When workflow.apply(workItem, "bill")
Then exception InvalidTransition (transitions valides définies workflow.yaml)
```

```
Given WorkItem status "validated" associé Project facturé
And InvoiceCreatedEvent dispatché pour ce Project
When listener BillRelatedWorkItems consume event
Then WorkItem.status devient "billed"
And WorkItemBilledEvent dispatché
```

```
Given WorkItem status "billed"
And InvoicePaidEvent dispatché
When listener MarkRelatedWorkItemsAsPaid consume event
Then WorkItem.status devient "paid"
And WorkItemPaidEvent dispatché
```

### Technical Notes
- ADR-0016 Q3.1 A 4 états (vs reco TL B 2 états MVP)
- ADR-0016 A-1 + A-2 + A-10
- `WorkItemStatus` enum Domain (`DRAFT`, `VALIDATED`, `BILLED`, `PAID`)
- Symfony Workflow component (déjà bundled framework)
- Cross-aggregate Application Layer listeners ACL `Invoice` → `WorkItem`
- **AT-3 vérification** : `InvoiceCreatedEvent` + `InvoicePaidEvent` existent ✅ (`src/Domain/Invoice/Event/`)
- **AT-3.2 acté** : étendre `InvoiceCreatedEvent` constructor avec `array<WorkItemId> $workItemIds = []` (default empty = backward compat). Application Layer use case `CreateInvoice` collecte WorkItems projet AVANT dispatch event. Listeners `BillRelatedWorkItemsOnInvoiceCreated` consomment payload directement (pas de query DB extra). Migration Doctrine ajoute colonne `status` table `work_item` avec default `'draft'`.
- Migration Doctrine : ajout colonne `status` table `work_item` (default `'draft'` rows existantes)
- Tests Integration Docker DB pour transitions valides + invalides

---

