# Module: Vacations / Time-Off (DDD CQRS bounded context)

> **DRAFT** — stories `INFERRED` from real CQRS implementation under `src/Application/Vacation` + `src/Domain/Vacation`.
> Source: `prd.md` §5.7 (FR-VAC-01..08). Generated 2026-05-04.

---

## US-038 — Demande de congé

> INFERRED from `RequestVacationCommand` + VOs `DateRange`, `DailyHours`, `VacationStatus`, `VacationType`.

- **Implements**: FR-VAC-01 — **Persona**: P-001 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** intervenant
**I want** poser une demande de congé (type, dates, heures journalières)
**So that** mon manager peut l'approuver et mon planning est ajusté.

### Acceptance Criteria
```
Given intervenant authentifié
When POST /vacations {type, start, end, daily_hours}
Then VacationStatus = REQUESTED + VacationRequested event
And notification au manager direct
```
```
Given chevauchement avec congé existant
Then refusé (InvalidVacationException)
```
```
Given dates passées
Then refusé (sauf cas exceptionnels paramétrés)
```

### Technical Notes
- VOs `DateRange`, `DailyHours` valident les invariants
- Couvre intervenants à temps partiel (DailyHours)

---

## US-039 — Approuver un congé

> INFERRED from `ApproveVacationCommand` + `VacationApproved` event.

- **Implements**: FR-VAC-02 — **Persona**: P-003 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** manager
**I want** approuver une demande de congé en attente
**So that** l'intervenant a confirmation et le planning se verrouille.

### Acceptance Criteria
```
Given vacation REQUESTED
When manager POST /vacations/{id}/approve
Then VacationStatus = APPROVED + VacationApproved event
And notification à l'intervenant
And planning chevauchant remis en cause si nécessaire
```
```
Given vacation déjà APPROVED ou REJECTED
Then InvalidStatusTransitionException
```

---

## US-040 — Refuser un congé

> INFERRED from `RejectVacationCommand` + `VacationRejected` event.

- **Implements**: FR-VAC-03 — **Persona**: P-003 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** manager
**I want** refuser une demande de congé avec un motif
**So that** l'intervenant comprend la décision.

### Acceptance Criteria
```
Given REQUESTED
When POST /vacations/{id}/reject {reason}
Then VacationStatus = REJECTED + VacationRejected event + notification
```
```
Given motif vide
Then refusé
```

---

## US-041 — Annuler un congé

> INFERRED from `CancelVacationCommand` + `VacationCancelled` + `NotificationType::VACATION_CANCELLED_BY_MANAGER`.

- **Implements**: FR-VAC-04 — **Persona**: P-001, P-003 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** intervenant ou manager
**I want** annuler un congé (avant ou pendant)
**So that** je gère un imprévu.

### Acceptance Criteria
```
Given APPROVED non commencé
When intervenant POST /vacations/{id}/cancel
Then CANCELLED + VacationCancelled event
```
```
Given manager annule un congé approuvé
Then notification VACATION_CANCELLED_BY_MANAGER + raison
```
```
Given vacation déjà CANCELLED
Then InvalidStatusTransitionException
```

---

## US-042 — Compter les jours approuvés

> INFERRED from `CountApprovedDaysQuery`.

- **Implements**: FR-VAC-05 — **Persona**: P-001, P-003, P-004 — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** intervenant, manager ou compta
**I want** connaître le nombre de jours de congé approuvés (sur l'année / période)
**So that** je suis le solde et je facture proprement.

### Acceptance Criteria
```
Given vacations multiples
When GET /vacations/count?contributor=X&period=2026
Then nombre exact (incluant fractions DailyHours)
```

---

## US-043 — Lister les congés d'un contributeur

> INFERRED from `GetContributorVacationsQuery`.

- **Implements**: FR-VAC-06 — **Persona**: P-001, P-003 — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** intervenant ou manager
**I want** lister les congés d'un contributeur
**So that** je vois l'historique et le futur.

### Acceptance Criteria
```
When GET /vacations?contributor=X
Then liste paginée avec status, type, dates
```

---

## US-044 — Lister congés en attente pour un manager

> INFERRED from `GetPendingVacationsForManagerQuery`.

- **Implements**: FR-VAC-07 — **Persona**: P-003 — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** manager
**I want** voir uniquement les demandes REQUESTED qui me concernent
**So that** je traite ma file rapidement.

### Acceptance Criteria
```
Given manager + équipe rattachée
When GET /vacations/pending
Then uniquement REQUESTED des contributeurs sous ma responsabilité
```

---

## US-045 — Garde des transitions de statut

> INFERRED from `InvalidStatusTransitionException` + `VacationStatus` VO.

- **Implements**: FR-VAC-08 — **Persona**: système — **Estimate**: 2 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** rejeter toute transition de statut illégale (ex: REJECTED → APPROVED)
**So that** l'intégrité du workflow est garantie.

### Acceptance Criteria
```
Given REJECTED
When tentative APPROVED
Then InvalidStatusTransitionException
```
```
Given matrice de transitions documentée
Then test couvre tous les cas illégaux
```

### Technical Notes
- Reférence implémentation pour autres BCs (état de l'art DDD du repo)

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-038 | Demande congé | FR-VAC-01 | 5 | Must |
| US-039 | Approuver congé | FR-VAC-02 | 3 | Must |
| US-040 | Refuser congé | FR-VAC-03 | 3 | Must |
| US-041 | Annuler congé | FR-VAC-04 | 3 | Must |
| US-042 | Compter jours approuvés | FR-VAC-05 | 2 | Must |
| US-043 | Lister congés contributeur | FR-VAC-06 | 2 | Must |
| US-044 | Pending pour manager | FR-VAC-07 | 2 | Must |
| US-045 | Guard transitions statut | FR-VAC-08 | 2 | Must |
| **Total** | | | **22** | |
