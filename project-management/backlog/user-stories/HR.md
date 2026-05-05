# Module: HR — Onboarding, Performance, Satisfaction

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.9 (FR-HR-01..08). Generated 2026-05-04.

---

## US-052 — Profil contributeur + skills/techno

> INFERRED from `Contributor`, `ContributorSkill`, `ContributorTechnology`.

- **Implements**: FR-HR-01 — **Persona**: P-001, P-003 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** intervenant ou manager
**I want** maintenir le profil contributeur (skills, technologies, niveau)
**So that** le staffing et la GPEC sont à jour.

### Acceptance Criteria
```
Given intervenant
When POST /contributor-skills / /contributor-technologies
Then liens persistés avec niveau (junior/senior/expert)
```

---

## US-053 — Onboarding template + tâches

> INFERRED from `OnboardingTemplate`, `OnboardingTask`, `OnboardingController`, `OnboardingTemplateController`.

- **Implements**: FR-HR-02 — **Persona**: P-001, P-003 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager
**I want** définir un template d'onboarding et l'appliquer aux nouveaux entrants
**So that** chaque nouveau recrut suit un parcours standardisé.

### Acceptance Criteria
```
When admin POST /admin/onboarding-templates {tasks[]}
Then template créé
```
```
Given nouveau contributeur
When applique template
Then OnboardingTask créés assignés à l'intervenant
```
```
Given progression
Then tableau de bord onboarding pour manager
```

---

## US-054 — Cycle de revue de performance

> INFERRED from `PerformanceReview`, `PerformanceReviewController`.

- **Implements**: FR-HR-03 — **Persona**: P-003 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager
**I want** lancer/planifier des revues de performance
**So that** je documente les évaluations annuelles/périodiques.

### Acceptance Criteria
```
When POST /performance-reviews {contributor, period, evaluator}
Then PerformanceReview créée statut "draft"
```
```
Given draft
When manager + intervenant complètent
Then statut "completed", verrouillée
```

---

## US-055 — Suivi de satisfaction contributeur

> INFERRED from `ContributorSatisfaction`, `ContributorSatisfactionController`.

- **Implements**: FR-HR-04 — **Persona**: P-001, P-003 — **Estimate**: 3 pts — **MoSCoW**: Should

### Card
**As** intervenant et manager
**I want** mesurer la satisfaction (auto-déclarée + observée)
**So that** je détecte les signaux faibles.

### Acceptance Criteria
```
When intervenant soumet score périodique
Then ContributorSatisfaction persisté
```
```
Given moyenne en baisse N périodes consécutives
Then alerte manager
```

---

## US-056 — Progression et niveau employé

> INFERRED from `ContributorProgress`, `EmployeeLevel`.

- **Implements**: FR-HR-05 — **Persona**: P-001, P-003 — **Estimate**: 3 pts — **MoSCoW**: Could

### Card
**As** intervenant et manager
**I want** suivre la progression dans la grille `EmployeeLevel`
**So that** carrière et rémunération sont objectivées.

### Acceptance Criteria
```
When promotion validée
Then EmployeeLevel mis à jour + ContributorProgress event
```

---

## US-057 — Sondage NPS interne et public

> INFERRED from `NpsSurvey`, `NpsController`, `NpsPublicController`.

- **Implements**: FR-HR-06 — **Persona**: P-001, P-003, P-007 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager (interne) ou client (public)
**I want** collecter un NPS périodique
**So that** je mesure l'attachement.

### Acceptance Criteria
```
Given lien NPS public {token}
When client soumet score 0-10 + commentaire
Then NpsSurvey persistée anonyme/identifiée
```
```
Given NPS interne
When intervenant soumet
Then auto-anonyme côté affichage
```

---

## US-058 — Tableau de bord RH

> INFERRED from `HrDashboardController`.

- **Implements**: FR-HR-07 — **Persona**: P-003 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager
**I want** un dashboard RH (effectifs, satisfaction, NPS, congés posés, niveaux)
**So that** je pilote la BU.

### Acceptance Criteria
```
When GET /hr-dashboard
Then KPI agrégés tenant-scoped
```

---

## US-059 — Gamification (badges, XP, leaderboard)

> INFERRED from `Achievement`, `Badge`, `XpHistory`, `BadgeController`, `LeaderboardController`.

- **Implements**: FR-HR-08 — **Persona**: P-001 — **Estimate**: 5 pts — **MoSCoW**: Could

### Card
**As** intervenant
**I want** gagner des badges/XP en réalisant des actions clés (saisie temps complète, projets terminés)
**So that** je suis engagé dans l'usage de la plateforme.

### Acceptance Criteria
```
Given action déclenchant un Achievement
When event consommé
Then Badge attribué + XpHistory + notification
```
```
When GET /leaderboard
Then top contributeurs par XP (tenant scoped, opt-in)
```

### Technical Notes
- Considérer impact RGPD (opt-in classement public)

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-052 | Profil contributeur | FR-HR-01 | 5 | Must |
| US-053 | Onboarding | FR-HR-02 | 5 | Should |
| US-054 | Performance review | FR-HR-03 | 5 | Should |
| US-055 | Satisfaction | FR-HR-04 | 3 | Should |
| US-056 | Progression/EmployeeLevel | FR-HR-05 | 3 | Could |
| US-057 | NPS interne+public | FR-HR-06 | 5 | Should |
| US-058 | Dashboard RH | FR-HR-07 | 5 | Should |
| US-059 | Gamification | FR-HR-08 | 5 | Could |
| **Total** | | | **36** | |
