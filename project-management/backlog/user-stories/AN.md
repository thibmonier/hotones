# Module: Analytics, KPIs, Forecasting & AI

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.10 (FR-AN-01..05). Generated 2026-05-04.

---

## US-060 — Surveillance KPI avec seuils

> INFERRED from `KpiThresholdExceededEvent` + `NotificationType::KPI_THRESHOLD_EXCEEDED`.

- **Implements**: FR-AN-01 — **Persona**: P-003, P-004, P-005 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** manager / compta / admin
**I want** définir des seuils sur des KPI (marge, win-rate, retard paiement, etc.) et être alerté en cas de dépassement
**So that** je détecte et corrige tôt.

### Acceptance Criteria
```
Given KPI avec seuil
When valeur observée franchit seuil
Then KpiThresholdExceededEvent + notification KPI_THRESHOLD_EXCEEDED
```
```
Given franchissement déjà notifié dans la fenêtre
Then pas de spam (debounce)
```

---

## US-061 — Dashboards multi-roles

> INFERRED from `SalesDashboardController`, `HrDashboardController`, `StaffingDashboardController`, `TreasuryController`, `ProjectHealthController`, `BackofficeDashboardController`.

- **Implements**: FR-AN-02 — **Persona**: P-003..P-005 — **Estimate**: 8 pts — **MoSCoW**: Must

### Card
**As** manager / admin / compta
**I want** consulter le dashboard correspondant à mon rôle (Sales, HR, Staffing, Treasury, Project health, Backoffice)
**So that** j'ai une vue d'ensemble pertinente.

### Acceptance Criteria
```
Given user ROLE_X
When GET /<dashboard>
Then données tenant-scoped, filtres période
```
```
Given KPI lourd
Then cache Redis 5-15 min
```

---

## US-062 — Forecasting & prédictions

> INFERRED from `Controller/Analytics/*`, `ForecastingController`, `PredictionsController`, `FactForecast`.

- **Implements**: FR-AN-03 — **Persona**: P-003, P-005 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** manager / admin
**I want** des prévisions (CA prochains mois, charge, marge)
**So that** je planifie les actions.

### Acceptance Criteria
```
When GET /analytics/forecasting
Then prévisions par segment (CA, charge, marge) avec intervalles
```
```
Given données insuffisantes
Then message explicite (intervalle large ou pas de prédiction)
```

### Technical Notes
- `FactForecast` = table de faits (date, métrique, valeur, scénario)
- Pipeline de calcul (cron) à documenter

---

## US-063 — Chatbot AI

> INFERRED from `ChatbotController`, `Service/AI/*`, `AI/Tool/*`, symfony/ai-bundle.

- **Implements**: FR-AN-04 — **Persona**: P-001..P-005 — **Estimate**: 8 pts — **MoSCoW**: Could

### Card
**As** utilisateur HotOnes
**I want** poser des questions en langage naturel ("ma marge sur le projet X ?", "qui est dispo en juin ?")
**So that** je gagne du temps face aux dashboards.

### Acceptance Criteria
```
Given user authentifié
When POST /chatbot/message {prompt}
Then réponse stream + usage des AI Tools (DB queries scoped tenant)
```
```
Given prompt hors périmètre
Then refus poli
```
```
Given prompt cross-tenant
Then refusé strictement (FR-IAM-05)
```

### Technical Notes
- Tools dans `src/AI/Tool` exposent capacités scoped
- Lock provider: env-driven (Anthropic / OpenAI / Gemini)
- Coût AI tracé (Sentry / Monolog)
- ⚠️ Risque R-07 lock-in et coût

---

## US-064 — Recommandations API

> INFERRED from route `/api/recommendations`.

- **Implements**: FR-AN-05 — **Persona**: P-002, P-003 — **Estimate**: 5 pts — **MoSCoW**: Could

### Card
**As** chef de projet ou manager
**I want** un endpoint qui me suggère des actions (rééquilibrer staffing, augmenter TJM, archiver projet)
**So that** je suis guidé.

### Acceptance Criteria
```
Given GET /api/recommendations
Then liste de recommandations triées par valeur attendue
```

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-060 | KPI seuil + alerte | FR-AN-01 | 5 | Must |
| US-061 | Dashboards multi-roles | FR-AN-02 | 8 | Must |
| US-062 | Forecasting | FR-AN-03 | 8 | Should |
| US-063 | Chatbot AI | FR-AN-04 | 8 | Could |
| US-064 | Recommandations API | FR-AN-05 | 5 | Could |
| **Total** | | | **34** | |
