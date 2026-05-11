# US-065 — Fil de notifications in-app

> **BC**: NTF  |  **Source**: archived NTF.md (split 2026-05-11)

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

