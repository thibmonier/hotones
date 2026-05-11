# US-035 — Timer running

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

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

