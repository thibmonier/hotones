# Module: Project Delivery

> **DRAFT** — stories `INFERRED` from codebase.
> Source: `project-management/prd.md` §5.4 (FR-PRJ-01..FR-PRJ-09)
> Generated: 2026-05-04

---

## US-020 — Gestion projets + sous-tâches

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

## US-021 — Journal d'événements projet

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

## US-022 — Score de santé projet

> INFERRED from `ProjectHealthScore` + `ProjectHealthController`.

- **Implements**: FR-PRJ-03
- **Persona**: P-002, P-003
- **Estimate**: 8 pts
- **MoSCoW**: Should

### Card
**As** chef de projet ou manager
**I want** un score de santé composite par projet (budget, planning, satisfaction, marge)
**So that** je détecte tôt les projets qui dérapent.

### Acceptance Criteria
```
Given projet en cours
When job batch s'exécute
Then ProjectHealthScore (0-100) calculé selon formule
And historique conservé
```
```
When GET /project-health
Then liste projets avec scores triés
```
```
Given score < seuil critique
Then ProjectBudgetAlertEvent ou KpiThresholdExceededEvent dispatché (FR-AN-01)
```

### Technical Notes
- Formule du score à expliciter (PO + tech)

---

## US-023 — Compétences et techno projet

> INFERRED from `ProjectSkill`, `ProjectTechnology`, `ProjectTechnologyController`.

- **Implements**: FR-PRJ-04
- **Persona**: P-002, P-003
- **Estimate**: 3 pts
- **MoSCoW**: Should

### Card
**As** chef de projet
**I want** taguer un projet avec ses compétences et technologies
**So that** je peux staffer les bons profils et capitaliser le savoir.

### Acceptance Criteria
```
Given projet
When ajout d'une Technology + Skill
Then liens persistés
```
```
When recherche/staffing
Then les contributeurs avec ces skills/techs sont remontés
```

---

## US-024 — Vue des risques projet

> INFERRED from `RiskController`.

- **Implements**: FR-PRJ-05
- **Persona**: P-002, P-003
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** chef de projet
**I want** déclarer les risques projet (probabilité, impact, mitigation)
**So that** j'anticipe.

### Acceptance Criteria
```
Given projet
When add risque {description, proba, impact, mitigation}
Then visible dans /risk
```

### Technical Notes
- Modèle Risk pas trouvé comme entité dédiée → vérifier (peut être dans Project ou JSON column)

---

## US-025 — Alerte budget projet

> INFERRED from `ProjectBudgetAlertEvent` + `NotificationType::PROJECT_BUDGET_ALERT`.

- **Implements**: FR-PRJ-06
- **Persona**: P-002, P-003
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** être alerté quand un projet approche/dépasse son budget
**So that** je négocie un avenant ou ralentis la consommation.

### Acceptance Criteria
```
Given projet avec budget = 100 jours-homme
When jours consommés ≥ 80% (seuil paramétrable)
Then ProjectBudgetAlertEvent
And notification PROJECT_BUDGET_ALERT
```
```
When dépassement > 100%
Then notification urgente (canal email + in-app)
```

---

## US-026 — Alerte marge basse

> INFERRED from `LowMarginAlertEvent` + `NotificationType::LOW_MARGIN_ALERT`.

- **Implements**: FR-PRJ-07
- **Persona**: P-002, P-003, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** manager / admin
**I want** être alerté quand la marge d'un projet descend sous le seuil
**So that** je réagis avant la perte.

### Acceptance Criteria
```
Given projet avec marge calculée = (CA − CJM × jours)
When marge % < seuil tenant
Then LowMarginAlertEvent + notification LOW_MARGIN_ALERT
```
```
Given projet en perte sèche (marge < 0)
Then escalade automatique (manager + admin)
```

---

## US-027 — Liste des projets à risque

> INFERRED from route `/at-risk`.

- **Implements**: FR-PRJ-08
- **Persona**: P-003, P-005
- **Estimate**: 3 pts
- **MoSCoW**: Should

### Card
**As** manager / admin
**I want** une vue unique des projets "à risque" (santé basse, budget dépassé, marge faible)
**So that** je priorise mes interventions.

### Acceptance Criteria
```
Given multiples projets
When GET /at-risk
Then liste filtrée triée par sévérité
```

---

## US-028 — Actions bulk projets

> INFERRED from routes `/bulk-archive`, `/bulk-delete`.

- **Implements**: FR-PRJ-09
- **Persona**: P-003, P-005
- **Estimate**: 3 pts
- **MoSCoW**: Could

### Card
**As** manager / admin
**I want** archiver/supprimer plusieurs projets en une fois
**So that** je nettoie rapidement.

### Acceptance Criteria
```
Given sélection N projets
When POST /bulk-archive
Then tous archivés en une transaction
```
```
Given suppression: confirmation explicite requise (irréversible)
```

### Technical Notes
- Soft delete recommandé (Gedmo)
- Limite tenant (interdire bulk hors tenant)

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-020 | Gestion projets+sous-tâches | FR-PRJ-01 | 8 | Must |
| US-021 | Journal événements projet | FR-PRJ-02 | 3 | Should |
| US-022 | Score santé projet | FR-PRJ-03 | 8 | Should |
| US-023 | Skills + techno projet | FR-PRJ-04 | 3 | Should |
| US-024 | Vue risques projet | FR-PRJ-05 | 5 | Should |
| US-025 | Alerte budget projet | FR-PRJ-06 | 5 | Must |
| US-026 | Alerte marge basse | FR-PRJ-07 | 5 | Must |
| US-027 | Projets à risque | FR-PRJ-08 | 3 | Should |
| US-028 | Bulk archive/delete | FR-PRJ-09 | 3 | Could |
| **Total** | | | **43** | |
