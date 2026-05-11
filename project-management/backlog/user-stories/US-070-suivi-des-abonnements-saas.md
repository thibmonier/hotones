# US-070 — Suivi des abonnements SaaS

> **BC**: SAAS  |  **Source**: archived SAAS.md (split 2026-05-11)

> INFERRED from `SaasSubscription`, `Subscription`, `SubscriptionController`, `SaasController`.

- **Implements**: FR-SAAS-02 — **Persona**: P-005 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** admin
**I want** suivre les abonnements (date début, fin, montant, renouvellement)
**So that** je maîtrise les coûts SaaS récurrents.

### Acceptance Criteria
```
When add subscription {service, period, cost, auto_renew}
Then SaasSubscription persistée
```
```
Given fin d'abonnement dans J-N
Then notification (si configurée)
```

---

