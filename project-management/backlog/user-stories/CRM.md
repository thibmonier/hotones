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

## US-012 — Lead funnel (capture publique + pipeline backend)

> INFERRED from `LeadCapture` entity + `CrmLeadController` + `LeadMagnetController`. Décision atelier 2026-05-15: **fusion** des FR-CRM-03 et FR-MKT-03 (anciennement US-077).

- **Implements**: FR-CRM-03 + FR-MKT-03 (fused)
- **Persona**: P-005 (admin), P-007 (visiteur)
- **Estimate**: 8 pts (élargi suite fusion)
- **MoSCoW**: Should

### Card
**As** plateforme HotOnes (visiteur côté entrée + admin côté traitement)
**I want** un funnel complet: capture publique via lead-magnets → email opt-in → livraison ressource → entrée dans le pipeline CRM avec qualification
**So that** je nourris le commerce avec des leads qualifiés sans rupture entre marketing et CRM.

### Acceptance Criteria

**Front capture (ex US-077)**
```
Given visiteur anonyme sur page lead-magnet
When soumet email + intérêt
Then LeadCapture créé statut "new"
And email de remerciement avec lien S3 signé vers ressource
```
```
Given email déjà en base
Then même ressource renvoyée (pas de doublon LeadCapture)
```
```
Given soumission abusive (rate-limit ou honeypot)
Then 429 + log
```

**Backend pipeline (ex US-012)**
```
Given admin authentifié
When GET /admin/crm
Then liste leads avec statuts (new / qualified / contacted / lost / won)
And conversion lead → Client possible (1 clic)
```
```
Given lead converti
Then Client créé tenant-scoped + lien `LeadCapture.convertedToClient`
```

### Technical Notes
- Rate limiter Symfony obligatoire (`/lead-magnet/*`).
- Honeypot anti-bot.
- Sitemap (FR-MKT-04) référence pages lead-magnet.
- Cohérence FR mapping: PRD §5.2 (CRM-03) et §5.14 (MKT-03) **mergées**; mettre à jour PRD prochaine itération.

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
| US-012 | Lead funnel (front + backend, fused with US-077) | FR-CRM-03 + FR-MKT-03 | 8 | Should |
| US-013 | Sync HubSpot | FR-CRM-04 | 8 | Should |
| US-014 | Dashboard ventes | FR-CRM-05 | 5 | Should |
| **Total** | | | **29** (+3 fusion) | |
