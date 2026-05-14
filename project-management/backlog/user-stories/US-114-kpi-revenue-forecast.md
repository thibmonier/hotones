# US-114 — KPI Revenue forecast (prévision CA glissante)

> **BC**: OPS  |  **Source**: EPIC-003 Phase 5 (sprint-025) — extension KPIs business

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 3 pts — **MoSCoW** : Must — **Sprint** : 025

### Card
**As** PO
**I want** une **prévision de chiffre d'affaires glissante** basée sur le pipeline (devis à signer pondérés + commandes confirmées non facturées)
**So that** j'anticipe la trésorerie entrante des 30 / 90 jours à venir et je pilote l'effort commercial.

### Acceptance Criteria

```
Given accès ROLE_ADMIN
When je vais sur /admin/business-dashboard
Then je vois métrique « Revenue forecast » avec :
  - forecast pondéré 30 / 90 jours (horizon glissant)
  - décomposition : commandes confirmées (signe/gagne) + devis pondérés (a_signer)
  - montant déjà facturé exclu (pas de double comptage)
```

```
Given des Orders avec statut et validUntil
When le forecast est calculé
Then commandes signe/gagne non facturées comptées à 100 %
And devis a_signer comptés pondérés par un coefficient de probabilité (défaut 0.3)
And Orders perdu/abandonne/standby exclus
And horizon = validUntil dans la fenêtre glissante considérée
```

```
Given forecast 30j < seuil configuré (défaut plancher tréso)
When dashboard affiché
Then KPI marqué warning (orange)
And alerte Slack si forecast 30j < seuil rouge (défaut configuré société)
```

### Technical Notes

- Domain Service `RevenueForecastCalculator` (pure PHP, testable Unit) — pattern KpiCalculator sp-024
- Source : `Order` (statuts `a_signer` / `signe` / `gagne`), `Invoice` pour exclusion déjà facturé
- Coefficient probabilité devis : hiérarchique (pattern US-108 seuils), défaut 0.3
- Repository read-model port + Doctrine adapter ; cache pool `cache.kpi` 1h TTL
- Cache decorator + invalidation : subscribers `OrderValidatedEvent` + `InvoiceCreatedEvent`
- Widget Twig dashboard + handler CQRS (pattern US-110/111/112)
- Alerte Slack `SlackAlertingService` (US-094)

### Tasks (scopées sprint-025 Planning P2 — voir `tasks/US-114-tasks.md`)

- [ ] T-114-01 [BE] Domain Service `RevenueForecastCalculator` + tests Unit (3 h)
- [ ] T-114-02 [BE] Repository read-model port + Doctrine adapter (2 h)
- [ ] T-114-03 [BE] Cache decorator + subscribers invalidation (`OrderValidatedEvent` + `InvoiceCreatedEvent`) (2 h)
- [ ] T-114-04 [FE-WEB] Widget Twig dashboard + handler CQRS (2 h)
- [ ] T-114-05 [BE] Alerte Slack seuil rouge forecast (1 h)
- [ ] T-114-06 [TEST] Tests Integration E2E (query + cache + flow event) (2 h)

### Dépendances

- ✅ EPIC-002 dashboard (US-093)
- ✅ EPIC-002 `SlackAlertingService` (US-094)
- ✅ Pattern KpiCalculator + `cache.kpi` pool (US-110/111/112 sprint-024)
- ✅ `OrderValidatedEvent` (statut signature) / `InvoiceCreatedEvent` (US-111)

---
