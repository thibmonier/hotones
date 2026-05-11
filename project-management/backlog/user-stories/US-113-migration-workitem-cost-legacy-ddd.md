# US-113 — Migration historique `WorkItem.cost` legacy → DDD aggregate

> **BC**: PRJ  |  **Source**: EPIC-003 Phase 4 (sprint-024) — AUDIT-WORKITEM-DATA Phase 1 (sprint-019)

- **Implements** : EPIC-003 — **Persona** : Tech Lead + PO — **Estimate** : 3 pts — **MoSCoW** : Should — **Sprint** : 024

### Card
**As** Tech Lead
**I want** migrer les `WorkItem.cost` legacy (flat persistence pré-DDD) vers le `WorkItem` aggregate DDD avec recalcul cohérent via `HourlyRate × WorkedHours`
**So that** les données historiques projets passent l'invariant data integrity > 95 % (trigger abandon ADR-0013 cas 3) et le calcul marge temps réel reste fiable sur l'historique.

### Acceptance Criteria

```
Given audit Phase 1 (AUDIT-WORKITEM-DATA, sprint-019) a identifié des irrégularités
  WorkItem.cost flat (cost stocké non recalculable depuis HourlyRate × WorkedHours)
When migration exécutée
Then chaque WorkItem legacy reçoit :
  - WorkItemId Value Object valide
  - HourlyRate Value Object (depuis Contributor.hourlyRate à la date du WorkItem)
  - WorkedHours Value Object (depuis legacy hours field)
  - cost recalculé = HourlyRate × WorkedHours
```

```
Given écart entre legacy cost et cost recalculé
When delta > 1 cent
Then WorkItem marqué `legacy_cost_drift` (col bool migration)
And rapport CSV exporté listant tous les drifts pour audit comptable manuel
And la migration log les drift counts par projet
```

```
Given écart global drift > 5 % du total cost projet
When rapport vérifié post-migration
Then trigger abandon ADR-0013 cas 3 activé (bloquer scaling)
And décision PO + Tech Lead requise (continuer ou rollback)
```

```
Given migration up
When relancée (idempotente)
Then aucun side-effect (skip déjà migrés via `migrated_at` col)
```

```
Given migration down
When relancée
Then restore exactement état pré-migration
And tests rollback passent
```

### Technical Notes

- **Approche** : commande Symfony `app:workitem:migrate-legacy-cost` (pas Doctrine migration auto — trop risquée sur historique)
- Batch processing 100 items/transaction pour éviter memory exhaustion
- Cols ajoutées table `work_item` (Doctrine migration séparée préalable) :
  - `migrated_at` datetime nullable
  - `legacy_cost_drift` bool default false
  - `legacy_cost_cents` int nullable (backup cost original)
- Dry-run mode obligatoire (`--dry-run`) avant exécution prod
- Rapport CSV export `var/migration/workitem-cost-drift-{date}.csv`
- Tests Integration migration up/down (réutilise pattern US-107)
- Coverage tests > 90 % (Domain pure)
- ⚠️ **Pré-requis prod** : backup BDD + fenêtre maintenance (volume estimé 2000-10000 WorkItem legacy)

### Tasks (à scoper sprint-024 Planning P2)

- [ ] T-113-01 [DB] Doctrine migration cols `migrated_at` + `legacy_cost_drift` + `legacy_cost_cents` (1 h)
- [ ] T-113-02 [BE] Domain Service `WorkItemMigrator` (recalcul + drift detection) + tests Unit (4 h)
- [ ] T-113-03 [BE] Commande Symfony `app:workitem:migrate-legacy-cost` avec `--dry-run` (2 h)
- [ ] T-113-04 [BE] Export CSV drift report (1 h)
- [ ] T-113-05 [TEST] Tests Integration migration up/down idempotente (3 h)
- [ ] T-113-06 [DOC] Runbook prod migration (backup + fenêtre + rollback) (1 h)
- [ ] T-113-07 [OPS] Exécution dry-run prod + analyse rapport drift (hors sprint, post-merge)

### Dépendances

- ✅ AUDIT-WORKITEM-DATA Phase 1 (sprint-019) — conclusions data quality
- ✅ EPIC-003 Phase 1 `WorkItem` aggregate (US-097)
- ✅ EPIC-003 Phase 2 ACL (US-098)
- ✅ EPIC-003 Phase 3 `MarginCalculator` (US-103/104)
- 🔄 Trigger abandon ADR-0013 cas 3 — gating condition

### Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Drift > 5 % global → blocage scaling | Moyenne | Haut | Dry-run obligatoire + audit comptable manuel avant exécution |
| Volume legacy > 10000 items → timeout migration | Faible | Moyen | Batch 100 + checkpoint resumable |
| Rollback nécessaire mi-migration | Faible | Haut | Backup `legacy_cost_cents` + migration down testée |
| HourlyRate historique manquant (Contributor inactif) | Moyenne | Moyen | Fallback `HourlyRate::fromCents(0)` + flag `legacy_no_rate` |

---
