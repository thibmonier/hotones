# US-117 — KPI Marge moyenne portefeuille

> **BC**: OPS  |  **Source**: EPIC-003 Phase 5 — extension KPIs business

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 3 pts — **MoSCoW** : Could — **Sprint** : backlog Phase 5 (reporté sprint-026+)

> ⚠️ **Reporté** : overflow capacité sprint-025 (12 pts ferme atteints avec US-114/115/116 + Sub-epic D dette). Candidate sprint-026.

### Card
**As** PO
**I want** la **marge moyenne pondérée du portefeuille de projets actifs** + sa tendance
**So that** je pilote la rentabilité globale au-delà du suivi projet par projet (US-103/104).

### Acceptance Criteria

```
Given accès ROLE_ADMIN
When je vais sur /admin/business-dashboard
Then je vois métrique « Marge moyenne portefeuille » avec :
  - marge moyenne pondérée par le montant des projets actifs
  - tendance ↗️ / ↘️ / → vs snapshot précédent
  - répartition : projets > seuil cible / sous seuil
```

```
Given des Projects actifs avec snapshot marge (US-107)
When la marge portefeuille est calculée
Then formule = Σ(marge_projet × montant_projet) / Σ(montant_projet)
And projets sans snapshot marge exclus (comptés séparément en « non calculés »)
And projets completed / cancelled exclus
```

```
Given marge portefeuille < seuil configuré (pattern US-108 hiérarchique)
When dashboard affiché
Then KPI marqué warning (orange)
And alerte Slack si marge < seuil rouge
```

### Technical Notes

- Domain Service `PortfolioMarginCalculator` — réutilise partiellement la logique `MarginAdoptionCalculator` (US-112) et le snapshot `Project.margin` (US-107)
- Source : `Project` actifs avec `margeCalculatedAt` non null + `Project.margin` snapshot
- Repository read-model + cache `cache.kpi` 1h ; invalidation `ProjectMarginRecalculatedEvent`
- Widget Twig + handler CQRS ; seuils hiérarchiques US-108 ; alerte Slack US-094

### Tasks (à scoper sprint-026 Planning P2)

- [ ] T-117-01 [BE] Domain Service `PortfolioMarginCalculator` + tests Unit (3 h)
- [ ] T-117-02 [BE] Repository read-model port + Doctrine adapter (2 h)
- [ ] T-117-03 [BE] Cache decorator + subscriber invalidation (2 h)
- [ ] T-117-04 [FE-WEB] Widget Twig dashboard + handler CQRS (2 h)
- [ ] T-117-05 [BE] Alerte Slack seuil rouge (1 h)
- [ ] T-117-06 [TEST] Tests Integration E2E (2 h)

### Dépendances

- ✅ US-107 persistence snapshot `Project.margin`
- ✅ US-112 `MarginAdoptionCalculator` (réutilisation partielle)
- ✅ Pattern KpiCalculator + `cache.kpi` pool

---
