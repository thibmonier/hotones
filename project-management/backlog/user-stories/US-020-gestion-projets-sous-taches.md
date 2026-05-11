# US-020 — Gestion projets + sous-tâches

> **BC**: PRJ  |  **Source**: archived PRJ.md (split 2026-05-11)

> INFERRED from `Project`, `ProjectTask`, `ProjectSubTask` + 4 controllers.

- **Implements**: FR-PRJ-01
- **Persona**: P-002, P-003
- **Estimate**: 8 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** créer/modifier un projet et y rattacher tâches + sous-tâches arborescentes
**So that** je structure la livraison.

### Acceptance Criteria
```
Given commande SIGNED
When je génère un projet à partir
Then Project créé + ProjectTask depuis OrderTask
```
```
Given Project actif
When add ProjectSubTask sur une ProjectTask
Then sous-tâche créée avec parent
```
```
Given projet avec sous-tâches non terminées
When tentative de fermeture
Then bloquée avec liste des items ouverts
```

---

