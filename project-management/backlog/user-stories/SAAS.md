# Module: SaaS Catalogue & Subscriptions

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.12 (FR-SAAS-01..03). Generated 2026-05-04.

---

## US-069 — Catalogue de fournisseurs SaaS

> INFERRED from `SaasProvider`, `SaasService`, `SaasProviderCrudController`, `SaasServiceCrudController`.

- **Implements**: FR-SAAS-01 — **Persona**: P-005 — **Estimate**: 3 pts — **MoSCoW**: Should

### Card
**As** admin
**I want** maintenir un catalogue de SaaS providers + services utilisés par ma société
**So that** je rationalise les outils.

### Acceptance Criteria
```
When CRUD /admin/saas-providers /admin/saas-services
Then entrées persistées tenant-scoped
```

---

## US-070 — Suivi des abonnements SaaS

> INFERRED from `SaasSubscription`, `Subscription`, `SubscriptionController`, `SaasController`.

- **Implements**: FR-SAAS-02 — **Persona**: P-005 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** admin
**I want** suivre les abonnements (date début, fin, montant, renouvellement)
**So that** je maîtrise les coûts SaaS récurrents.

### Acceptance Criteria
```
When add subscription {service, period, cost, auto_renew}
Then SaasSubscription persistée
```
```
Given fin d'abonnement dans J-N
Then notification (si configurée)
```

---

## US-071 — Catégoriser les services SaaS

> INFERRED from `ServiceCategory`, `ServiceCategoryCrudController`.

- **Implements**: FR-SAAS-03 — **Persona**: P-005 — **Estimate**: 2 pts — **MoSCoW**: Could

### Card
**As** admin
**I want** classer les services par catégorie (CRM, Dev, Design, etc.)
**So that** mon analyse SaaS est lisible.

### Acceptance Criteria
```
When CRUD /admin/service-categories
Then catégories persistées
```

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-069 | Catalogue providers/services | FR-SAAS-01 | 3 | Should |
| US-070 | Abonnements SaaS | FR-SAAS-02 | 5 | Should |
| US-071 | Catégories services | FR-SAAS-03 | 2 | Could |
| **Total** | | | **10** | |
