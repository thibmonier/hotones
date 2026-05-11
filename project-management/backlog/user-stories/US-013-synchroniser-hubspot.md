# US-013 — Synchroniser HubSpot

> **BC**: CRM  |  **Source**: archived CRM.md (split 2026-05-11)

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

