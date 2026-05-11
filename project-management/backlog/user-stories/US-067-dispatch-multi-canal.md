# US-067 — Dispatch multi-canal

> **BC**: NTF  |  **Source**: archived NTF.md (split 2026-05-11)

> INFERRED from `NotificationChannel` enum + `Infrastructure/Notification/*` + symfony/notifier.

- **Implements**: FR-NTF-03 — **Persona**: système — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** dispatcher chaque notification sur les canaux configurés (in-app, email; futur SMS/Slack)
**So that** la stratégie de communication est unifiée.

### Acceptance Criteria
```
Given event business
When notification émise
Then NotificationChannel résolus selon préférences user
And envoi via symfony/notifier (queue)
```
```
Given canal email indisponible
Then retry messenger; échec persisté pour replay
```

---

