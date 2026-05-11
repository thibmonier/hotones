# US-110 — KPI DSO (Days Sales Outstanding)

> **BC**: OPS  |  **Source**: EPIC-003 Phase 4 (sprint-024 kickoff) — ADR-0013 KPI #1

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 3 pts — **MoSCoW** : Must — **Sprint** : 024

### Card
**As** PO
**I want** mesurer le **DSO** (Days Sales Outstanding) en jours sur 30/90/365 jours rolling
**So that** je pilote la trésorerie et identifie les clients lents à payer.

### Acceptance Criteria

```
Given accès ROLE_ADMIN
When je vais sur /admin/business-dashboard
Then je vois la métrique DSO avec :
  - DSO 30 jours rolling (moyenne pondérée)
  - DSO 90 jours rolling
  - DSO 365 jours rolling
  - tendance vs période précédente (↗️ / ↘️ / →)
```

```
Given factures émises avec dates d'émission et dates de paiement
When DSO calculé
Then formule = Σ(date_paiement - date_émission) × montant_payé / Σ(montant_payé)
And factures non payées exclues du calcul (ou pondérées par "âge actuel")
```

```
Given facture impayée > 90 jours
When dashboard affiché
Then DSO marqué warning (orange) si > seuil configuré (défaut 45j)
And alerte Slack si DSO 30j > seuil rouge (défaut 60j)
```

### Technical Notes

- Domain Service `DsoCalculator` (pure PHP, testable Unit sans DB)
- Doctrine query optimisée : 1 query SQL avec aggregation par période
- Cache Redis 1 heure (DSO change lentement)
- Réutilise pattern persistence snapshot US-107 (cols `dso_cents_at` / `dso_days_30` / `dso_days_90` sur table métriques) — invalidation event-driven `InvoicePaidEvent`
- Seuils configurables hiérarchiquement (cf US-108 pattern : global → Client) — défaut global 45j warning / 60j alert
- Pas de migration prérequise (utilise table `invoice` existante avec `paidAt` Gedmo Timestampable)

### Tasks (à scoper sprint-024 Planning P2)

- [ ] T-110-01 [BE] Domain Service `DsoCalculator` + tests Unit (3 h)
- [ ] T-110-02 [BE] Repository query optimisée 30/90/365 jours (2 h)
- [ ] T-110-03 [BE] Subscriber `InvoicePaidEvent` invalidation cache (1 h)
- [ ] T-110-04 [FE-WEB] Widget Twig + Stimulus + indicateur tendance (2 h)
- [ ] T-110-05 [BE] Alerte Slack seuil rouge réutilise `SlackAlertingService` US-094 (1 h)
- [ ] T-110-06 [TEST] Tests Integration query + cache invalidation (2 h)

### Dépendances

- ✅ EPIC-002 dashboard `/admin/business-dashboard` (US-093)
- ✅ EPIC-002 `SlackAlertingService` (US-094)
- ✅ US-108 pattern configurabilité hiérarchique seuils

---
