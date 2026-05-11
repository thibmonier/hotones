# US-012 — Lead funnel (capture publique + pipeline backend)

> **BC**: CRM  |  **Source**: archived CRM.md (split 2026-05-11)

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

