# Gestion de Projet

Ce répertoire contient la gestion de projet.

## Structure

```
project-management/
├── backlog/
│   ├── index.md              # Index avec tous les statuts
│   ├── epics/                # EPICs du projet
│   ├── user-stories/         # User Stories
│   └── tasks/                # Tasks non assignées à un sprint
├── sprints/
│   └── sprint-XXX/
│       ├── sprint-goal.md    # Objectif et infos du sprint
│       ├── board.md          # Kanban board
│       └── tasks/            # Tasks du sprint
└── metrics/
    ├── velocity.md           # Vélocité par sprint
    └── burndown.md           # Burndown charts
```

## Workflow

1. `/project:add-epic` - Créer un EPIC
2. `/project:add-story` - Ajouter des User Stories
3. `/project:move-story US-XXX sprint-N` - Planifier le sprint
4. `/project:add-task` ou `/project:decompose-tasks` - Créer les tâches
5. `/project:board` - Suivre l'avancement
6. `/project:move-task` - Mettre à jour les statuts

## Statuts

| Icône | Statut | Description |
|-------|--------|-------------|
| 🔴 | To Do | Pas encore commencé |
| 🟡 | In Progress | En cours |
| ⏸️ | Blocked | Bloqué |
| 🟢 | Done | Terminé |

## Capacity planning par nature de story (OPS-016)

> Action retro sprint-005 #1. Mis en place sprint-006 J1.

Plutôt qu'utiliser une vélocité moyenne unique, le projet calibre la capacité
en pondérant chaque story par sa nature. Les coefficients sont calibrés sur
les sprints livrés et recalibrés à chaque rétrospective si la livraison réelle
diverge significativement (≥ 25 %) du plan.

### Coefficients (calibrés sprints 004 + 005)

| Nature | Coefficient | Note |
|---|---:|---|
| `doc-only` | ×1.5 | doc + tableau + xref + ADR. Sprint-005 a livré ~1 pt en 30 min. |
| `refactor` | ×1.0 | conversion mécanique sur classes existantes. |
| `test` | ×0.8 | écrire un test demande analyse cas + fixture. |
| `infra` | ×0.7 | hook + workflow + secrets gating. |
| `feature-be` | ×0.5 | backend nouveau, Doctrine + tests. |
| `feature-fe` | ×0.4 | UI Symfony UX/Twig + tests fonctionnels. |

### Calcul de la capacité projetée

1. Identifier le mix par nature dans le sprint planifié (% pts par catégorie).
2. Calculer la moyenne pondérée des coefficients.
3. Capacité projetée = capacité brute × moyenne pondérée.

```
capacité_projetée = Σ(pts_par_nature × coefficient_nature) × focus_factor
```

### Exemple sprint-006

| Nature | Pts | % | Coef | Pondéré |
|---|---:|---:|---:|---:|
| refactor | 8 | 36 % | 1.0 | 8.0 |
| test | 8 | 36 % | 0.8 | 6.4 |
| infra | 2 | 9 % | 0.7 | 1.4 |
| doc-only | 4 | 18 % | 1.5 | 6.0 |

Moyenne pondérée ≈ **0.99**. Capacité brute 32 pts × 0.99 ≈ **31.7 pts** projetés.
Engagé 22 pts → marge de 9.7 pts (30 %).

### Recalibrage

À chaque sprint review, comparer pts livrés / pts projetés par nature :

- Si livraison > 125 % du projeté pour une nature → **augmenter** son coefficient.
- Si livraison < 75 % du projeté pour une nature → **diminuer** son coefficient.
- Tracer chaque ajustement dans la retro et mettre à jour ce tableau.

### Ce que ça n'est pas

- ❌ Une formule magique : la calibration reste un retour d'expérience.
- ❌ Un substitut au Sprint Goal : le mix doit servir l'objectif, pas l'inverse.
- ❌ Un outil de comparaison entre sprints à mix très différents.
