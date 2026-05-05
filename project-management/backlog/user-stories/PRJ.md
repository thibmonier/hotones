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
Then 4 sous-scores calculés ∈ [0,100]: budgetScore, timelineScore, velocityScore, qualityScore
And composite score = 0.25 * budgetScore + 0.25 * timelineScore + 0.25 * velocityScore + 0.25 * qualityScore
And healthLevel mappé: [0,30]=critical / [30,60]=at-risk / [60,80]=healthy / [80,100]=excellent
And ProjectHealthScore persisté avec recommendations[] + details[] (JSON)
```
```
When GET /risks/projects (RiskController) ou /project-health (ProjectHealthController)
Then liste projets avec scores triés desc + healthLevel + nb à risque par catégorie
```
```
Given score franchit seuil critique (=30) à la baisse
Then KpiThresholdExceededEvent dispatché (FR-AN-01) + notification
```

### Technical Notes
- **Pondération validée (atelier 2026-05-15)**: 25/25/25/25 (V1; rebalancing futur possible).
- **Mapping healthLevel** (V1): critical < 30 ≤ at-risk < 60 ≤ healthy < 80 ≤ excellent.
- Implémentation: `ProjectHealthScore` entity + service de calcul (à isoler si dispersé).
- Test unitaire pondération composite + tests de mapping healthLevel.

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

## US-024 — Score de risque projet (calculé)

> INFERRED from `RiskController` + `ProjectRiskAnalyzer` + `AnalyzeProjectRisksMessage`.

- **Implements**: FR-PRJ-05
- **Persona**: P-002, P-003
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** chef de projet ou manager
**I want** un `riskLevel` calculé automatiquement par projet (critical / high / medium / low)
**So that** je priorise mes interventions sans saisie manuelle.

### Acceptance Criteria
```
Given projet actif
When ProjectRiskAnalyzer s'exécute (cron ou async via AnalyzeProjectRisksMessage)
Then riskLevel calculé à partir des 5 signaux:
  1. Budget consommé % (>80% = high, >100% = critical)
  2. Glissement planning (jours retard / jours initiaux > 20% = high, > 40% = critical)
  3. Marge projet % (<10% = high, <0 = critical)
  4. Score satisfaction contributeur (<5/10 = medium)
  5. Dépendances bloquées (≥1 dep critique = high)
And riskLevel = max() des niveaux des signaux atteints
```
```
When GET /risks/projects
Then liste atRiskProjects + stats {total, atRisk, critical, high, medium}
```
```
Given riskLevel passe à critical
Then notification + dispatch KPI_THRESHOLD_EXCEEDED
```

### Technical Notes
- Pas d'entité `Risk` dédiée: sortie = array `['analysis' => ['riskLevel' => ...]]` (computed).
- Seuils paramétrables côté `CompanySettings` (V2).
- Tests par signal + test combinatoire (max niveau).

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
