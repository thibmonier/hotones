# US-100 — Domain Service `DailyHoursValidator` + `DailyHoursWarningException`

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

- **Implements**: EPIC-003 Phase 3 — **Persona**: P-001 — **Estimate**: 2 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** Tech Lead
**I want** un Domain Service `DailyHoursValidator` qui calcule `dailyMaxHours` depuis `EmploymentPeriod` et détecte dépassement journalier
**So that** UC `RecordWorkItem` peut valider invariant journalier (ADR-0015) sans coupling direct à `EmploymentPeriod` repository.

### Acceptance Criteria

```
Given contributorId existant + date donnée
And EmploymentPeriod actif (date dans intervalle) avec weeklyHours = 35 et workTimePercentage = 100
When DailyHoursValidator::dailyMaxHours(contributorId, date)
Then retourne WorkedHours(7.0) (35 × 100 / 100 / 5 = 7h)
```

```
Given contributorId + date sans EmploymentPeriod actif
When DailyHoursValidator::dailyMaxHours(contributorId, date)
Then NoActiveEmploymentPeriodException levée
```

```
Given EmploymentPeriod weeklyHours = 35, workTimePercentage = 80
When DailyHoursValidator::dailyMaxHours
Then retourne WorkedHours(5.6) (35 × 80 / 100 / 5)
```

```
Given existingWorkItems totaling 6h pour (contributor, date)
And command WorkItem.hours = 2h (donc dailyTotal = 8h)
And dailyMaxHours = 7h
When DailyHoursValidator::isExceeded(contributorId, date, additionalHours)
Then retourne true (dépassement)
```

```
Given existingWorkItems totaling 5h
And command WorkItem.hours = 1h (dailyTotal = 6h)
And dailyMaxHours = 7h
When DailyHoursValidator::isExceeded
Then retourne false (pas de dépassement)
```

### Technical Notes
- ADR-0016 A-4 + ADR-0015 invariant journalier
- **AT-3.1 acté** : ACL adapter pattern. Créer `EmploymentPeriodRepositoryInterface` Domain + `EmploymentPeriodSnapshot` DTO Domain + `DoctrineEmploymentPeriodAdapter` Infrastructure wrapping flat `App\Repository\EmploymentPeriodRepository`. Migration Domain pure entity reportée sprint-026+.
- `DailyHoursWarningException` Domain — non bloquante (Q2.4) mais propagée UC pour UI override
- Tests Unit pure host PHP (sans Docker) — `EmploymentPeriodRepositoryInterface` mockée
- Tests Integration Docker DB — `DoctrineEmploymentPeriodAdapter` validé contre vraie BDD

---

