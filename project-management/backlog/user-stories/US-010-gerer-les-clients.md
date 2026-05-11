# US-010 — Gérer les clients

> **BC**: CRM  |  **Source**: archived CRM.md (split 2026-05-11)

> INFERRED from `Client` entity + `ClientController`.

- **Implements**: FR-CRM-01
- **Persona**: P-002, P-003, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** chef de projet, manager ou admin
**I want** créer/lister/modifier/archiver les clients de ma société
**So that** je dispose d'une base CRM consolidée pour facturer et piloter.

### Acceptance Criteria
```
Given admin/CP authentifié sur société Acme
When POST /clients avec nom + identifiant fiscal + adresse
Then client créé avec scope tenant=Acme
And visible uniquement par utilisateurs Acme (FR-IAM-05)
```
```
Given liste >20 clients
When GET /clients
Then pagination automatique (KnpPaginator)
```
```
Given client lié à projets actifs
When tentative de suppression
Then refusée avec message; archivage proposé
```

### Technical Notes
- Soft delete attendu (Gedmo SoftDeleteable à confirmer)
- Validation identifiant fiscal selon pays (cf. `.claude/rules/16-i18n.md`)

---

