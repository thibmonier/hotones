# Module: CRM & Sales Pipeline

> **DRAFT** — stories `INFERRED` from codebase.
> Source: `project-management/prd.md` §5.2 (FR-CRM-01..FR-CRM-05)
> Generated: 2026-05-04

---

## US-010 — Gérer les clients

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

## US-011 — Gérer les contacts client

> INFERRED from `ClientContact` + `ClientContactController`.

- **Implements**: FR-CRM-02
- **Persona**: P-002, P-003
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** rattacher des contacts (interlocuteurs) à un client
**So that** je sais qui appeler/écrire pour chaque dossier.

### Acceptance Criteria
```
Given client existant
When POST /clients/{id}/contacts {nom, email, téléphone, fonction}
Then contact créé et associé
```
```
When email invalide
Then 422 avec violations Symfony Validator
```

---

## US-012 — Pipeline de leads CRM

> INFERRED from `LeadCapture` entity + `CrmLeadController` + `LeadMagnetController`.

- **Implements**: FR-CRM-03
- **Persona**: P-005, P-007
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** admin / commerce
**I want** capturer des leads via formulaires publics (lead magnets) et suivre leur progression
**So that** je nourris mon pipeline commercial.

### Acceptance Criteria
```
Given visiteur sur page lead-magnet
When soumet email + intérêt
Then LeadCapture créé statut "new"
And email de remerciement / livraison de la ressource
```
```
Given admin connecté
When GET /admin/crm
Then voit la liste des leads + statuts (new/qualified/contacted/lost/won)
```
```
Given soumission spam (rate-limit ou heuristique)
Then bloquée avec 429
```

### Technical Notes
- Rate limiter Symfony à appliquer
- Honeypot anti-bot recommandé

---

## US-013 — Synchroniser HubSpot

> INFERRED from `HubSpotSettings` + `Service/HubSpot/*` + `HubSpotSettingsController`.

- **Implements**: FR-CRM-04
- **Persona**: P-005
- **Estimate**: 8 pts
- **MoSCoW**: Should

### Card
**As** admin
**I want** connecter mon compte HubSpot et synchroniser leads / contacts / deals
**So that** HotOnes reflète mon CRM externe sans double saisie.

### Acceptance Criteria
```
Given admin avec API key HubSpot
When POST /admin/hubspot/settings
Then settings chiffrés stockés
And test de connexion OK
```
```
Given settings actifs
When job de sync s'exécute (scheduler)
Then leads/contacts/deals créés/mis à jour côté HotOnes
```
```
Given API HubSpot indisponible
Then job retry via messenger; aucun blocage UI
```

### Technical Notes
- Async via symfony/messenger
- Gestion erreur + circuit-breaker (R-10)
- Mapping HubSpot ↔ entités HotOnes à documenter

---

## US-014 — Tableau de bord ventes

> INFERRED from `SalesDashboardController` + `FactForecast`.

- **Implements**: FR-CRM-05
- **Persona**: P-003, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** manager / admin
**I want** voir un dashboard ventes (pipeline, win-rate, prévisions)
**So that** je pilote mon activité commerciale.

### Acceptance Criteria
```
Given user ROLE_MANAGER+
When GET /sales-dashboard
Then voit: CA signé, CA en pipeline, win-rate, top clients, prévisions trimestre
```
```
Given multi-tenant
Then données scoped à la société courante
```
```
When période modifiée (filtres)
Then KPI recalculés
```

### Technical Notes
- Cache Redis pour KPI lourds
- Forecast ML/statistique (FactForecast) — algo à expliciter

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-010 | Gérer clients | FR-CRM-01 | 5 | Must |
| US-011 | Gérer contacts client | FR-CRM-02 | 3 | Must |
| US-012 | Pipeline leads CRM | FR-CRM-03 | 5 | Should |
| US-013 | Sync HubSpot | FR-CRM-04 | 8 | Should |
| US-014 | Dashboard ventes | FR-CRM-05 | 5 | Should |
| **Total** | | | **26** | |
