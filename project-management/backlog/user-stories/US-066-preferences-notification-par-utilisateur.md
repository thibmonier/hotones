# US-066 — Préférences notification par utilisateur

> **BC**: NTF  |  **Source**: archived NTF.md (split 2026-05-11)

> INFERRED from `NotificationPreference`, `NotificationSetting`, `NotificationSettingsController`.

- **Implements**: FR-NTF-02 — **Persona**: tous authentifiés — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** utilisateur
**I want** choisir, par type de notification, le canal (in-app, email, off)
**So that** je ne reçois que ce que je veux.

### Acceptance Criteria
```
When PUT /admin/notifications/settings {preferences{}}
Then NotificationPreference persistées par utilisateur
```
```
Given utilisateur a désactivé email pour QUOTE_LOST
When event QuoteStatusChangedEvent(LOST)
Then aucune email envoyée à cet utilisateur
```

---

