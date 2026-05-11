# US-099 — UC `RecordWorkItem` avec invariant journalier flexible

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

- **Implements**: EPIC-003 Phase 3 — **Persona**: P-001 (intervenant) + P-002 (manager) — **Estimate**: 5 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** intervenant (P-001) ou manager (P-002)
**I want** saisir mes heures travaillées par projet (et tâche optionnellement) avec validation invariant journalier flexible
**So that** mon WorkItem DDD est créé en `draft` (ou `validated` si je suis manager), avec audit trail traçable et possibilité d'override warning seuil journalier.

### Acceptance Criteria

```
Given intervenant authentifié sur projet existant
When POST UC RecordWorkItem {date, projetId, hours, taskId?, comment?, userOverride: false}
Then WorkItem créé status "draft"
And dailyTotal pour (contributorId, date) recalculé
And WorkItemRecorded event dispatché
```

```
Given user ROLE_MANAGER ou ROLE_ADMIN authentifié
When POST UC RecordWorkItem {...}
Then WorkItem créé direct status "validated" (transition workflow validate auto)
And event WorkItemRecorded + WorkItemValidated dispatchés
```

```
Given dailyTotal + hours.requested > dailyMaxHours (calculé EmploymentPeriod)
And command.userOverride = false
When POST UC RecordWorkItem
Then DailyHoursWarningException levée
And UI affiche warning + checkbox confirmation override
```

```
Given dailyTotal + hours.requested > dailyMaxHours
And command.userOverride = true (user a confirmé)
When POST UC RecordWorkItem
Then WorkItem créé (override accepté)
And AuditLog enregistre override (qui/quand/dailyTotal/dailyMaxHours/motif)
```

```
Given WorkItem existant date < 7 jours
And projet associé pas encore facturé (Invoice.status != billed/paid)
When PUT UC RecordWorkItem (édition)
Then WorkItem mis à jour
```

```
Given WorkItem existant date > 7 jours OU projet billed/paid
And user n'est PAS ROLE_ADMIN
When PUT UC RecordWorkItem (édition)
Then refus 403 Forbidden
```

```
Given user ROLE_ADMIN
When PUT UC RecordWorkItem sur WorkItem verrouillé (date > 7 jours OU projet facturé)
Then édition autorisée
And AuditLog enregistre override admin (qui/quand/avant/après)
```

### Technical Notes
- ADR-0016 Q1.2 5 champs MVP + comment optionnel + taskId optionnel (ADR-0015)
- ADR-0016 Q1.3 step heures 0.25h (15 min)
- ADR-0016 Q2.1 auto-save (pas de submit hebdo explicite)
- ADR-0016 Q2.2 B+D édition rétroactive 7 jours OU pas si projet facturé
- ADR-0016 Q2.3 admin override + audit log
- ADR-0016 Q2.4 warning override user (vs Exception bloquante reco TL)
- ADR-0016 Q3.2 role-based managers self-validate
- Domain Service `DailyHoursValidator` (US-100) injecté
- Repository `WorkItemRepositoryInterface::findByContributorAndDate` (sprint-020 #207)
- ⚠️ OQ-1 + OQ-2 ADR-0016 à valider Sprint Planning P2 (interprétation managers + manager d'un WorkItem)

---

