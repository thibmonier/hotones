# Module: Time Tracking

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.6 (FR-TIM-01..04). Generated 2026-05-04.

---

## US-034 — Saisie de temps

> INFERRED from `Timesheet`, `TimesheetController`, `Service/Timesheet/*`.

- **Implements**: FR-TIM-01 — **Persona**: P-001 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** intervenant
**I want** saisir mon temps quotidien/hebdo par projet/tâche
**So that** mes heures sont facturables et la rentabilité calculée.

### Acceptance Criteria
```
Given intervenant sur projet
When POST /timesheets {date, projet, tâche, durée}
Then Timesheet créé statut "draft"
```
```
Given total > 24h sur un jour
Then refusé
```
```
Given week-end
Then accepté avec warning (heures sup)
```

---

## US-035 — Timer running

> INFERRED from `RunningTimer`.

- **Implements**: FR-TIM-02 — **Persona**: P-001 — **Estimate**: 3 pts — **MoSCoW**: Should

### Card
**As** intervenant
**I want** démarrer/arrêter un timer en temps réel
**So that** je n'oublie pas de saisir.

### Acceptance Criteria
```
When POST /timer/start {projet, tâche}
Then RunningTimer créé
```
```
When POST /timer/stop
Then RunningTimer converti en Timesheet draft
```
```
Given timer actif > N heures
Then notification "timer toujours actif?"
```

---

## US-036 — Validation des temps

> INFERRED from `TimesheetPendingValidationEvent` + `NotificationType::TIMESHEET_PENDING_VALIDATION`.

- **Implements**: FR-TIM-03 — **Persona**: P-001, P-002, P-003 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** chef de projet / manager
**I want** valider/refuser les timesheets soumis
**So that** la facturation et la rentabilité reposent sur du temps validé.

### Acceptance Criteria
```
Given intervenant soumet semaine
When POST /timesheets/{id}/submit
Then statut "pending_validation" + TimesheetPendingValidationEvent
And notification au manager
```
```
Given manager
When approve
Then statut "approved", verrouillé en modification
```
```
Given refus
Then statut "rejected" + commentaire requis
And notification à l'intervenant
```

---

## US-037 — Détection timesheet hebdo manquant

> INFERRED from `NotificationType::TIMESHEET_MISSING_WEEKLY`.

- **Implements**: FR-TIM-04 — **Persona**: P-001, P-002 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** intervenant (et son manager)
**I want** un rappel automatique si je n'ai pas saisi en fin de semaine
**So that** la collecte de temps est exhaustive.

### Acceptance Criteria
```
Given intervenant actif
When vendredi soir + temps hebdo < seuil minimal
Then notification TIMESHEET_MISSING_WEEKLY
```
```
Given intervenant en congé approuvé
Then pas de rappel
```

### Technical Notes
- Job scheduler (`Schedule.php`)
- Tient compte des congés (FR-VAC)

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-034 | Saisie temps | FR-TIM-01 | 5 | Must |
| US-035 | Timer running | FR-TIM-02 | 3 | Should |
| US-036 | Validation temps | FR-TIM-03 | 5 | Must |
| US-037 | Rappel hebdo manquant | FR-TIM-04 | 3 | Must |
| **Total** | | | **16** | |
