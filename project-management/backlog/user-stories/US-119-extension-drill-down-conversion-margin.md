# US-119 — Extension drill-down par client (Conversion + Margin)

> **BC**: OPS  |  **Source**: EPIC-003 Phase 5 (sprint-026) — extension widgets sp-024/025

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 2 pts — **MoSCoW** : Should — **Sprint** : 026

### Card
**As** PO
**I want** un **drill-down par client** + **export CSV** sur les widgets Conversion (US-115) et Margin adoption (US-112)
**So that** je complète l'analyse client-par-client déjà disponible sur DSO/lead time (US-116).

### Acceptance Criteria

```
Given accès ROLE_ADMIN et widgets Conversion rate + Margin adoption affichés
When je clique sur un widget KPI
Then une vue drill-down liste le détail par client :
  - Conversion : taux par client + count devis émis/signés sur la fenêtre
  - Margin adoption : projets par client + fraîcheur (fresh/warning/critical)
And la liste est triée par valeur décroissante (clients à problèmes en tête)
```

```
Given une vue drill-down Conversion ou Margin affichée
When je clique « Export CSV »
Then un fichier CSV est téléchargé avec colonnes :
  client_name, valeur_kpi, sample_count, fenêtre
And l'export respecte la fenêtre rolling sélectionnée
And l'export est multi-tenant (société courante uniquement)
```

```
Given aucune donnée sur la fenêtre sélectionnée
When la vue drill-down est affichée
Then un état vide explicite est rendu (pas d'erreur, pas de CSV vide silencieux)
```

### Technical Notes

- **Pas de nouveau calculateur** — extension des read-models existants `ConversionRateReadModelRepository` (sp-025 US-115) + `MarginAdoptionReadModelRepository` (sp-024 US-112)
- Méthode `findAllClientsAggregated` à ajouter (pattern US-116 T-116-01)
- Réutilise controller `BusinessDashboardDrillDownController` (US-116) — étendre regex route `dso|lead-time|conversion|margin`
- Réutilise `KpiDrillDownCsvExporter` (US-116 T-116-03) — extension générique
- Cache : réutilise `cache.kpi` pool (clés `<kpi>.clients_aggregated.*`)
- Vue Twig drill-down générique existante (US-116) — adapter pour 4 KPIs

### Tasks (scopées sprint-026 Planning P2 — voir `tasks/US-119-tasks.md`)

- [ ] T-119-01 [BE] Étendre read-models Conversion + Margin : `findAllClientsAggregated` (1.5 h)
- [ ] T-119-02 [FE-WEB] Étendre controller drill-down + route regex + Twig adapt 4 KPIs (2 h)
- [ ] T-119-03 [BE] Export CSV générique (extension KpiDrillDownCsvExporter) (1 h)
- [ ] T-119-04 [TEST] Tests Integration drill-down + Functional CSV (1.5 h)

### Dépendances

- ✅ US-115 ConversionRateReadModelRepository (sp-025)
- ✅ US-112 MarginAdoptionReadModelRepository (sp-024)
- ✅ US-116 `BusinessDashboardDrillDownController` + `KpiDrillDownCsvExporter` (sp-025)
- ✅ Pattern `findAllClientsAggregated` (sp-025 US-116 T-116-01)

---
