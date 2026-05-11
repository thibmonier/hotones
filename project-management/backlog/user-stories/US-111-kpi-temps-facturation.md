# US-111 — KPI temps de facturation (lead time devis signé → facture émise)

> **BC**: OPS  |  **Source**: EPIC-003 Phase 4 (sprint-024 kickoff) — ADR-0013 KPI #2

- **Implements** : EPIC-003 — **Persona** : PO — **Estimate** : 3 pts — **MoSCoW** : Must — **Sprint** : 024

### Card
**As** PO
**I want** mesurer le **temps de facturation** (lead time entre devis signé et facture émise) en jours
**So that** j'identifie les goulots d'étranglement administratifs entre commercial et compta.

### Acceptance Criteria

```
Given accès ROLE_ADMIN
When je vais sur /admin/business-dashboard
Then je vois métrique « temps de facturation » avec :
  - médiane lead time 30 / 90 / 365 jours rolling
  - p75 + p95 (queues lentes)
  - top 3 clients avec lead time le plus élevé
```

```
Given devis signés et factures émises avec dates
When lead time calculé
Then formule = date_facture_émise - date_devis_signé
And devis convertis en facture sur la période considérée uniquement
And devis sans facture exclus (compté séparément en backlog facturation)
```

```
Given lead time médian 30j > seuil configuré (défaut 14 jours)
When dashboard affiché
Then KPI marqué warning (orange)
And alerte Slack si médiane 30j > seuil rouge (défaut 30 jours)
```

### Technical Notes

- Domain Service `BillingLeadTimeCalculator` (pure PHP, testable Unit)
- Aggregat root `Quote` exposé `signedAt` (déjà existant) + `Invoice.emittedAt`
- Doctrine query : JOIN `Quote.invoice` + filtre `Quote.signedAt IS NOT NULL`
- Cache Redis 1 heure
- Persistence snapshot pattern US-107 (cols `billing_lead_time_p50_30d` / `_p95_30d` sur table métriques) — invalidation `InvoiceEmittedEvent`
- Seuils hiérarchiques pattern US-108
- Top 3 clients : sous-query `GROUP BY client_id ORDER BY AVG(lead_time) DESC LIMIT 3`

### Tasks (à scoper sprint-024 Planning P2)

- [ ] T-111-01 [BE] Domain Service `BillingLeadTimeCalculator` + tests Unit (3 h)
- [ ] T-111-02 [BE] Repository query avec percentiles p50/p75/p95 (2 h)
- [ ] T-111-03 [BE] Subscriber `InvoiceEmittedEvent` invalidation (1 h)
- [ ] T-111-04 [FE-WEB] Widget Twig dashboard + top 3 clients (2 h)
- [ ] T-111-05 [BE] Alerte Slack seuil rouge (réutilise SlackAlertingService) (1 h)
- [ ] T-111-06 [TEST] Tests Integration query + percentiles (2 h)

### Dépendances

- ✅ EPIC-002 dashboard (US-093)
- ✅ EPIC-002 `SlackAlertingService` (US-094)
- ✅ US-110 pattern KPI rolling 30/90/365 (similaire DSO)

---
