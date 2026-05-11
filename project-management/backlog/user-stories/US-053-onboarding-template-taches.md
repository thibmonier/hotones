# US-053 — Onboarding template + tâches

> **BC**: HR  |  **Source**: archived HR.md (split 2026-05-11)

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

