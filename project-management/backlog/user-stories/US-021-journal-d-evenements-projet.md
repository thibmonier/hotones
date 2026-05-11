# US-021 — Journal d'événements projet

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from `ProjectEvent`.

- **Implements**: FR-PRJ-02
- **Persona**: P-002, P-003
- **Estimate**: 3 pts
- **MoSCoW**: Should

### Card
**As** chef de projet
**I want** voir le journal d'événements (changement statut, ajout tâche, alerte budget)
**So that** je comprends l'historique sans fouiller les logs.

### Acceptance Criteria
```
Given mutation sur projet (statut, budget, équipe)
When sauvegarde
Then ProjectEvent persisté avec timestamp + auteur (Blameable)
```
```
When GET /projects/{id}/events
Then liste paginée chronologique
```

---

