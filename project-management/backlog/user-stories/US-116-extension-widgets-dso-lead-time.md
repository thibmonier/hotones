# US-116 — Extension widgets DSO / lead time (drill-down + export CSV)

> **BC**: OPS  |  **Source**: EPIC-003 Phase 5 (sprint-025) — extension KPIs business

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 2 pts — **MoSCoW** : Should — **Sprint** : 025

### Card
**As** PO
**I want** un **drill-down par client** et un **export CSV** sur les widgets DSO (US-110) et temps de facturation (US-111) déjà livrés
**So that** j'exploite les KPIs au-delà de l'agrégat — analyse client par client et reporting hors dashboard.

### Acceptance Criteria

```
Given accès ROLE_ADMIN et widgets DSO + lead time affichés
When je clique sur un widget KPI
Then une vue drill-down liste le détail par client :
  - DSO : DSO moyen par client + nombre de factures payées sur la fenêtre
  - lead time : lead time moyen par client + nombre de devis convertis
And la liste est triée par valeur décroissante (clients lents en tête)
```

```
Given une vue drill-down DSO ou lead time affichée
When je clique « Export CSV »
Then un fichier CSV est téléchargé avec colonnes :
  client_name, valeur_kpi, sample_count, fenêtre
And l'export respecte la fenêtre rolling sélectionnée (30 / 90 / 365 j)
And l'export est multi-tenant (société courante uniquement)
```

```
Given aucune donnée sur la fenêtre sélectionnée
When la vue drill-down est affichée
Then un état vide explicite est rendu (pas d'erreur, pas de CSV vide silencieux)
```

### Technical Notes

- **Pas de nouveau calculateur** — extension des read-models existants `DsoReadModelRepository` + `BillingLeadTimeReadModelRepository` (sprint-024)
- Réutilise les agrégations top clients déjà calculées (US-110/111 exposent `topSlowClients`) — étendre à la liste complète
- Nouvelle route `/admin/business-dashboard/drill-down/{kpi}` (kpi ∈ dso|lead-time) — controller Presentation
- Export CSV : pattern `DriftReportCsvExporter` (US-113 T-113-04) réutilisable — `fputcsv` + `Content-Disposition`
- Cache : réutilise `cache.kpi` pool (mêmes clés que widgets parents — pas d'invalidation supplémentaire)
- Vue Twig drill-down + lien depuis widgets existants ; voter `ROLE_ADMIN`

### Tasks (scopées sprint-025 Planning P2 — voir `tasks/US-116-tasks.md`)

- [ ] T-116-01 [BE] Étendre read-models DSO + lead time : méthode `findAllClientsAggregated` (2 h)
- [ ] T-116-02 [FE-WEB] Controller drill-down + route + voter + vue Twig (2 h)
- [ ] T-116-03 [BE] Export CSV (réutilise pattern `DriftReportCsvExporter`) (1.5 h)
- [ ] T-116-04 [TEST] Tests Integration drill-down + Functional export CSV (1.5 h)

### Dépendances

- ✅ US-110 KPI DSO + US-111 KPI lead time (sprint-024) — read-models à étendre
- ✅ Pattern `DriftReportCsvExporter` (US-113 T-113-04)
- ✅ `cache.kpi` pool

---
