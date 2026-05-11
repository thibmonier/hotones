# US-005 — Isolation multi-tenant par Company

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from README + `Company` entity + `BusinessUnit` + multi-tenant claim.

- **Implements**: FR-IAM-05
- **Source**: `Company`, `CompanySettings`, `BusinessUnit`
- **Persona**: tous
- **Estimate**: 8 pts (audit + tests régression)
- **MoSCoW**: Must
- **⚠️ Risk**: R-03 — mécanisme de filtre tenant non localisé au scan

### Card
**As** utilisateur d'une société Acme
**I want** que toutes mes requêtes soient automatiquement filtrées sur ma société
**So that** je ne peux jamais lire ni modifier les données de la société Concurrent.

### Acceptance Criteria
```
Given user Alice de société Acme
And user Bob de société Concurrent
When Alice fait GET /clients
Then ne voit aucun client de Concurrent
```
```
Given Alice tente GET /clients/{id-de-Concurrent}
Then 404 (pas 403, pour anti-énumération)
```
```
Given un seed cross-tenant directement en BDD
When Alice fait toute requête sur ce ressource
Then absent du résultat
```

### Technical Notes
- À implémenter via Doctrine SQLFilter + TenantContext (cf. `.claude/rules/14-multitenant.md`)
- Tests d'isolation obligatoires
- Couvre toutes les entités tenant-scoped (~50 sur 63)

---

