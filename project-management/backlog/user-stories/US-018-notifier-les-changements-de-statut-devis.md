# US-018 — Notifier les changements de statut devis

> **BC**: ORD  |  **Source**: archived ORD.md (split 2026-05-11)

> INFERRED from `QuoteStatusChangedEvent` + `NotificationType::QUOTE_TO_SIGN/WON/LOST`.

- **Implements**: FR-ORD-04
- **Persona**: P-002, P-003, P-005
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** chef de projet, manager ou admin
**I want** être notifié quand un devis change de statut
**So that** je réagis vite (relancer un client, lancer un projet, archiver un perdu).

### Acceptance Criteria
```
Given devis change de PENDING à WON
When event QuoteStatusChangedEvent dispatché
Then notification QUOTE_WON créée pour CP + manager
And canaux configurés (in-app, email selon NotificationPreference)
```
```
Given event consommé en async (messenger Redis)
Then aucune latence sur la requête HTTP qui a déclenché le changement
```

### Technical Notes
- Event subscriber dans `Infrastructure/Notification`
- `NotificationChannel` enum
- Cf. FR-NTF-03

---

