# US-081 — Scheduler cron

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `Schedule.php`, `Scheduler/*`, `SchedulerEntry`, `SchedulerEntryCrudController`.

- **Implements**: FR-OPS-03 — **Persona**: P-006 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** superadmin
**I want** définir et superviser les jobs planifiés (sync HubSpot/Boond, recalcul KPI, rappels timesheet)
**So that** la plateforme tourne en autonomie.

### Acceptance Criteria
```
When admin POST /admin/scheduler-entry {expression, command}
Then SchedulerEntry persisté
And cron-expression validée
```
```
Given exécution périodique
Then trace + sortie + statut accessibles
```

### Technical Notes
- symfony/scheduler + dragonmantank/cron-expression

---

