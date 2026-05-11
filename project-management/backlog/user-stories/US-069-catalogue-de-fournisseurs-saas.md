# US-069 — Catalogue de fournisseurs SaaS

> **BC**: SAAS  |  **Source**: archived SAAS.md (split 2026-05-11)

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

