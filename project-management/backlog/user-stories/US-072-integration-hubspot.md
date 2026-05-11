# US-072 — Intégration HubSpot

> **BC**: INT  |  **Source**: archived INT.md (split 2026-05-11)

> INFERRED from `HubSpotSettings`, `Service/HubSpot/*`, `HubSpotSettingsController`.

- **Implements**: FR-INT-01 — **Persona**: P-005 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** admin
**I want** intégrer HubSpot pour synchroniser leads, contacts, deals
**So that** mes données CRM externes alimentent HotOnes.

### Acceptance Criteria
```
When admin POST /admin/hubspot {api_key}
Then settings chiffrés stockés + test connexion
```
```
Given sync planifiée
When job s'exécute
Then leads/contacts/deals reflétés HotOnes (idempotent)
```
```
Given erreur API HubSpot
Then retry exponentiel via messenger; alerte admin si N échecs consécutifs
```

### Technical Notes
- Cf. US-013 (recouvrement: FR-CRM-04 et FR-INT-01 sont jumelles — fusionner ou différencier "settings" vs "sync runtime")
- ⚠️ Doublon potentiel à arbitrer

---

