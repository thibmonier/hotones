# US-093 — Dashboard 7 KPIs business

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

- **Implements** : EPIC-002 — **Persona** : PO — **Estimate** : 5 pts — **MoSCoW** : Should — **Sprint** : 017

### Card
**As** PO
**I want** dashboard prod avec 7 KPIs business pilotables
**So that** je peux mesurer la traction commerciale + la rentabilité.

### Acceptance Criteria
```
Given accès admin
When je vais sur /admin/business-dashboard
Then j'observe en temps réel :
  - DAU (Daily Active Users) + MAU
  - Projets créés / jour (sur 30j)
  - Devis signés / mois
  - Factures émises (count + montant total) / mois
  - Taux conversion devis → projet (%)
  - Revenu trail 30 jours
  - Marge moyenne par projet (€)
```

### Technical Notes
- Route `/admin/business-dashboard` protégée ROLE_ADMIN
- Twig template + Stimulus controller (refresh auto 5 min)
- Queries Doctrine optimisées (cache Redis 5 min)
- Pas d'export CSV initial (sprint-018)

### Tasks (à scoper sprint-017)
- [ ] T-093-01 [BE] DashboardKpiService avec 7 méthodes (4 h)
- [ ] T-093-02 [BE] Controller `/admin/business-dashboard` + cache (1 h)
- [ ] T-093-03 [FE-WEB] Twig template + Stimulus auto-refresh (2 h)
- [ ] T-093-04 [TEST] Tests Unit Service KPI (1,5 h)

---

