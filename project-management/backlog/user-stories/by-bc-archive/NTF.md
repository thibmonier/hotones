# Module: Notifications

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.11 (FR-NTF-01..04). Generated 2026-05-04.

---

## US-065 — Fil de notifications in-app

> INFERRED from `Notification` + `NotificationController` + routes `/api/unread`, `/all`.

- **Implements**: FR-NTF-01 — **Persona**: tous authentifiés — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** utilisateur authentifié
**I want** consulter mes notifications in-app (non lues, toutes)
**So that** je suis informé sans email overload.

### Acceptance Criteria
```
When GET /api/unread
Then liste des Notifications non lues triées desc
```
```
When marque comme lu
Then read_at set + compteur décrémenté
```
```
Given >100 notifications
Then pagination
```

---

## US-066 — Préférences notification par utilisateur

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

## US-067 — Dispatch multi-canal

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

## US-068 — 11 types de notification couvrant les événements clés

> INFERRED from `NotificationType` enum (11 cases).

- **Implements**: FR-NTF-04 — **Persona**: tous authentifiés — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** modéliser 11 types de notifications (QUOTE_TO_SIGN, QUOTE_WON, QUOTE_LOST, PROJECT_BUDGET_ALERT, LOW_MARGIN_ALERT, CONTRIBUTOR_OVERLOAD_ALERT, TIMESHEET_PENDING_VALIDATION, PAYMENT_DUE_ALERT, KPI_THRESHOLD_EXCEEDED, TIMESHEET_MISSING_WEEKLY, VACATION_CANCELLED_BY_MANAGER)
**So that** chaque événement métier est tracé et personnalisable.

### Acceptance Criteria
```
Given chaque event business
Then mapping vers NotificationType correct
```
```
Given internationalisation
Then libellé localisé (`getLabel`) selon locale tenant
```

### Technical Notes
- Couvre §5.3, §5.4, §5.5, §5.6, §5.7, §5.8, §5.10
- Étendre la liste = enum case + traduction + handler

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-065 | Fil notifications in-app | FR-NTF-01 | 3 | Must |
| US-066 | Préférences user | FR-NTF-02 | 5 | Must |
| US-067 | Dispatch multi-canal | FR-NTF-03 | 5 | Must |
| US-068 | 11 types notification | FR-NTF-04 | 5 | Must |
| **Total** | | | **18** | |
