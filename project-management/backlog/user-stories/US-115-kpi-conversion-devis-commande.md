# US-115 — KPI Taux de conversion devis → commande

> **BC**: OPS  |  **Source**: EPIC-003 Phase 5 (sprint-025) — extension KPIs business

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 3 pts — **MoSCoW** : Must — **Sprint** : 025

### Card
**As** PO
**I want** mesurer le **taux de conversion devis → commande** sur fenêtres glissantes
**So that** je pilote la performance commerciale et détecte les baisses de transformation.

### Acceptance Criteria

```
Given accès ROLE_ADMIN
When je vais sur /admin/business-dashboard
Then je vois métrique « Taux de conversion » avec :
  - ratio conversion 30 / 90 / 365 jours rolling
  - décomposition : devis émis / devis signés sur la période
  - tendance ↗️ / ↘️ / → vs fenêtre précédente
```

```
Given des Orders avec date d'émission et statut
When le taux est calculé
Then formule = count(Orders signe + gagne) / count(Orders émis hors standby) sur la fenêtre
And date d'émission = createdAt de l'Order
And Orders standby exclus du dénominateur (en attente, pas un échec)
And Orders perdu / abandonne comptés au dénominateur (échec de conversion)
```

```
Given taux conversion 30j < seuil configuré (défaut warning 40 %)
When dashboard affiché
Then KPI marqué warning (orange)
And alerte Slack si taux 30j < seuil rouge (défaut 25 %)
```

### Technical Notes

- Domain Service `ConversionRateCalculator` (pure PHP, testable Unit) — pattern KpiCalculator sp-024
- Source : `Order` — `createdAt` (émission) + statut (`signe`/`gagne` = converti, `perdu`/`abandonne` = échec, `standby` = exclu)
- Repository read-model port + Doctrine adapter ; cache pool `cache.kpi` 1h TTL
- Cache decorator + invalidation : subscriber `OrderValidatedEvent` (changement statut)
- Tendance : delta vs fenêtre précédente (pattern US-110 `StableDelta < 1`)
- Seuils hiérarchiques (pattern US-108) ; widget Twig + handler CQRS
- Alerte Slack `SlackAlertingService` (US-094)

### Tasks (scopées sprint-025 Planning P2 — voir `tasks/US-115-tasks.md`)

- [ ] T-115-01 [BE] Domain Service `ConversionRateCalculator` + tendance + tests Unit (3 h)
- [ ] T-115-02 [BE] Repository read-model port + Doctrine adapter (2 h)
- [ ] T-115-03 [BE] Cache decorator + subscriber `OrderValidatedEvent` invalidation (2 h)
- [ ] T-115-04 [FE-WEB] Widget Twig dashboard + handler CQRS + indicateur tendance (2 h)
- [ ] T-115-05 [BE] Alerte Slack seuil rouge conversion (1 h)
- [ ] T-115-06 [TEST] Tests Integration E2E (query + cache + flow event) (2 h)

### Dépendances

- ✅ EPIC-002 dashboard (US-093) + `SlackAlertingService` (US-094)
- ✅ Pattern KpiCalculator + `cache.kpi` pool (US-110/111/112 sprint-024)
- ✅ `OrderValidatedEvent`

---
