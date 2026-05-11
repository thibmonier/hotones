# Module: Planning, Staffing & Workload

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.5 (FR-PLN-01..05). Generated 2026-05-04.

---

## US-029 — Planning de capacité

> INFERRED from `Planning`, `PlanningSkill`, `PlanningController`, `PlanningOptimizationController`.

- **Implements**: FR-PLN-01 — **Persona**: P-002, P-003 — **Estimate**: 8 pts — **MoSCoW**: Must

### Card
**As** chef de projet ou manager
**I want** affecter des contributeurs à des projets sur des plages de dates
**So that** je sécurise la livraison et lisse la charge.

### Acceptance Criteria
```
Given contributeur disponible et projet actif
When POST /planning {contributor, project, dates, daily_hours}
Then Planning créé; conflits potentiels remontés
```
```
Given optimisation
When POST /planning/optimize
Then propositions d'allocation tenant compte des skills (PlanningSkill)
```
```
Given contributeur en congé approuvé chevauchant
Then refus avec explication (voir FR-VAC-02)
```

---

## US-030 — Tableau de bord staffing

> INFERRED from `StaffingDashboardController`.

- **Implements**: FR-PLN-02 — **Persona**: P-003 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** manager
**I want** voir le staffing global (qui est sur quoi, dispo prochaine)
**So that** j'arbitre les priorités.

### Acceptance Criteria
```
When GET /staffing
Then matrice contributeurs × semaines avec taux d'occupation
```

---

## US-031 — Prédiction charge (heuristique)

> INFERRED from `WorkloadPredictionController` + `Service/Planning/{PlanningOptimizer,ProjectPlanningAssistant,TaceAnalyzer}`.

- **Implements**: FR-PLN-03 — **Persona**: P-002, P-003 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** chef de projet ou manager
**I want** une prédiction de charge sur horizon 3 / 6 / 12 mois (filtrable par profil ou contributeur)
**So that** j'anticipe les sur/sous-charges et j'arbitre staffing.

### Acceptance Criteria
```
Given pipeline (devis + projets) + planning + capacité contributeur
When GET /staffing/prediction?months=3|6|12&profiles=[]&contributors=[]
Then analysePipeline() retourne {pipeline, workloadByMonth, alerts, totalPotentialDays}
And jours staffés pondérés par probabilité OrderStatus (cf US-062)
And alertes par contributeur:
  - warn: charge > 80% capacité hebdo
  - critical: charge > 100% capacité hebdo
```
```
Given absence de données
Then alerte vide + message UI explicite
```

### Technical Notes
- **Méthode V1 validée (atelier 2026-05-15)**: heuristique simple. Pas de ML.
- Formule: charge = somme (jours_staffés × proba_OrderStatus) / capacité_contributeur.
- Probabilités OrderStatus: cf US-062 (cohérence cross-features).
- Horizons proposés: 3, 6, 12 mois (whitelist).
- Cache Redis 15 min.

---

## US-032 — Détection surcharge contributeur

> INFERRED from `ContributorOverloadAlertEvent` + `NotificationType::CONTRIBUTOR_OVERLOAD_ALERT`.

- **Implements**: FR-PLN-04 — **Persona**: P-001, P-002, P-003 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** manager (et l'intervenant lui-même)
**I want** être alerté qu'un contributeur dépasse sa capacité hebdomadaire
**So that** on rééquilibre avant épuisement.

### Acceptance Criteria
```
Given heures planifiées > 100% capacité hebdo
When recalcul (event)
Then ContributorOverloadAlertEvent + notification CONTRIBUTOR_OVERLOAD_ALERT
```
```
Given surcharge persistante > N semaines
Then escalade au manager
```

---

## US-033 — Mes tâches du jour

> INFERRED from `MyTasksController`.

- **Implements**: FR-PLN-05 — **Persona**: P-001, P-002 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** intervenant ou chef de projet
**I want** voir mes tâches actives du jour/semaine
**So that** je sais quoi faire en premier.

### Acceptance Criteria
```
When GET /my-tasks
Then liste tâches assignées (ProjectTask + ProjectSubTask) triées par priorité/échéance
```

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-029 | Planning capacité | FR-PLN-01 | 8 | Must |
| US-030 | Dashboard staffing | FR-PLN-02 | 5 | Should |
| US-031 | Prédiction charge | FR-PLN-03 | 8 | Should |
| US-032 | Alerte surcharge | FR-PLN-04 | 5 | Must |
| US-033 | Mes tâches | FR-PLN-05 | 3 | Must |
| **Total** | | | **29** | |
