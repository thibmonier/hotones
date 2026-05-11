# US-112 — KPI % projets adoption marge temps réel

> **BC**: OPS  |  **Source**: EPIC-003 Phase 4 (sprint-024 kickoff) — ADR-0013 KPI #3

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 2 pts — **MoSCoW** : Should — **Sprint** : 024

### Card
**As** PO
**I want** mesurer le **% de projets adoption marge temps réel** (projets avec `margin_calculated_at` < 7 jours)
**So that** je quantifie l'adoption du MVP EPIC-003 (calcul marge temps réel) et identifie les projets en dette d'usage.

### Acceptance Criteria

```
Given accès ROLE_ADMIN
When je vais sur /admin/business-dashboard
Then je vois métrique « adoption marge » avec :
  - % projets actifs avec margin_calculated_at < 7 jours
  - % projets actifs avec margin_calculated_at entre 7-30 jours (warning)
  - % projets actifs avec margin_calculated_at NULL ou > 30 jours (stale)
  - count absolu chaque catégorie
```

```
Given projet actif (status != "archived")
When margin snapshot date évaluée
Then projet classé :
  - "fresh" si margin_calculated_at < 7 jours
  - "stale_warning" si 7 jours ≤ margin_calculated_at < 30 jours
  - "stale_critical" si margin_calculated_at NULL ou > 30 jours
```

```
Given adoption < seuil configuré (défaut 60%)
When dashboard affiché
Then KPI marqué warning (orange)
And alerte Slack si adoption < seuil rouge (défaut 40%) sur 7 jours consécutifs
```

### Technical Notes

- Réutilise `Project.marginCalculatedAt` (déjà persisté via US-107)
- Domain Service `MarginAdoptionCalculator` (pure PHP, simple)
- Doctrine query single : `SELECT COUNT(*) GROUP BY CASE WHEN margin_calculated_at ... END`
- Pas de cache prérequis (query rapide, recalcul à la demande dashboard)
- Trigger abandon ADR-0013 cas 2 : < 3 utilisations alerte dépassement / mois post prod = gadget. Ce KPI est l'indicateur du trigger.
- Seuils hiérarchiques pattern US-108 (global seul ici, pas client/project override)

### Tasks (à scoper sprint-024 Planning P2)

- [ ] T-112-01 [BE] Domain Service `MarginAdoptionCalculator` + tests Unit (2 h)
- [ ] T-112-02 [BE] Repository query CASE WHEN classification (1 h)
- [ ] T-112-03 [FE-WEB] Widget Twig dashboard 3 catégories + bar chart (1,5 h)
- [ ] T-112-04 [BE] Alerte Slack seuil rouge persistant 7j (1,5 h)
- [ ] T-112-05 [TEST] Tests Unit + Integration query (1 h)

### Dépendances

- ✅ US-107 persistence `Project.marginCalculatedAt` (sprint-023)
- ✅ EPIC-002 dashboard (US-093)
- ✅ EPIC-002 `SlackAlertingService` (US-094)
- 🔄 Trigger abandon ADR-0013 cas 2 — indicateur clé

---
