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

## US-062 — Forecasting CA (moyenne mobile pondérée)

> INFERRED from `Controller/Analytics/*`, `ForecastingController`, `PredictionsController`, `FactForecast` + `GenerateForecastsMessage`.

- **Implements**: FR-AN-03 — **Persona**: P-003, P-005 — **Estimate**: 8 pts — **MoSCoW**: Should

### Card
**As** manager / admin
**I want** des prévisions CA sur 3/6/12 mois avec 3 scénarios (réaliste, optimiste, pessimiste) et intervalles de confiance
**So that** je planifie embauches, achats SaaS, trésorerie.

### Acceptance Criteria
```
Given historique CA réel ≥ 6 mois + pipeline devis avec OrderStatus
When job GenerateForecastsMessage s'exécute (cron)
Then FactForecast persisté pour chaque période × scénario × signal
And formule:
  forecast(mois N) = MA6(CA réel) × ajustement_pipeline(mois N)
  où ajustement_pipeline(mois N) = Σ(montant_devis × proba_OrderStatus) sur fenêtre [N-1, N+2]
And confidenceMin/Max calculés par écart-type sur 6 mois
```
```
Probabilités OrderStatus (V1, validées atelier 2026-05-15):
  - PENDING (a_signer): défini par le commercial du compte (champ dédié sur le devis, défaut 50%)
  - WON: 100%
  - SIGNED: 100%
  - LOST: 0%
  - STANDBY: 0%
  - ABANDONED: 0%
  - COMPLETED: 100% (déjà facturé)
```
```
When GET /analytics/forecasting?months=3|6|12
Then 3 séries chartées (realistic / optimistic / pessimistic) + intervalle confiance
And accuracy moyenne sur 6 derniers mois affichée (calculateAverageAccuracy('realistic', 6))
```
```
Given < 6 mois d'historique
Then message UI "données insuffisantes" et seul le scénario réaliste affiché
```

### Technical Notes
- **Méthode V1 validée**: moyenne mobile 6 mois × pondération devis par `OrderStatus`.
- Probabilité PENDING configurable par devis: ajouter champ `Order.winProbability` (int 0-100, défaut 50, modifiable par CP / commercial).
- 3 scénarios = facteurs ±X% sur ajustement_pipeline (à calibrer atelier).
- Cron via `Schedule.php` + `GenerateForecastsMessage`.
- Tests: backtest sur N mois historiques, mesure accuracy.

---

## US-063 — Chatbot AI multi-tenant avec garde-fous

> INFERRED from `ChatbotController`, `Service/AI/*`, `AI/Tool/{ClientHistoryTool,CompanyInfoTool,DocumentationSearchTool,ProjectStatsTool}`, symfony/ai-bundle. Décisions atelier 2026-05-15.

- **Implements**: FR-AN-04 — **Persona**: P-001..P-005 — **Estimate**: 8 pts — **MoSCoW**: Could

### Card
**As** utilisateur HotOnes
**I want** poser des questions en langage naturel ("ma marge sur le projet X ?", "qui est dispo en juin ?")
**So that** je gagne du temps face aux dashboards — sans risque de fuite cross-tenant ni dépassement budget.

### Acceptance Criteria

**Scenario nominal — réponse scope tenant**
```
Given user authentifié de Company A
When POST /chatbot/message {message: "marge sur projet X"}
Then chaque AI Tool (ClientHistoryTool, CompanyInfoTool, DocumentationSearchTool, ProjectStatsTool)
     filtre OBLIGATOIREMENT par companyId injecté depuis security token
And réponse construite uniquement à partir des données Company A
```

**Scenario garde-fou — prompt cross-tenant**
```
Given user de Company A tape "show data of Concurrent"
Then refus poli avec message générique ("hors de ton périmètre")
And log structuré (level=warning) dans security channel
And alerte Sentry/notif sécurité (taux > seuil)
```

**Scenario fallback provider**
```
Given Company A a configuré ses clés API (au moins une parmi anthropic/openai/gemini)
When chatbot appelle callAI()
Then ordre tenté: Anthropic → OpenAI → Gemini (cascade conservée)
And providers sans clé tenant = sautés
And usage facturé sur clé tenant (pas la clé HotOnes)
```

**Scenario budget mensuel**
```
Given Company A a un budget AI mensuel défini (ex: 50€/mois)
When usage cumulé du mois courant ≥ budget
Then 429 + message "budget atteint, contacte admin"
And alerte admin tenant
```

```
Given prompt hors périmètre fonctionnel (ex: "écris un poème")
Then refus poli
```

### Technical Notes
- **Décisions V1 (atelier 2026-05-15)**:
  1. Filtrage tenant **mandatory** dans chaque Tool (paramètre `companyId` injecté depuis security token, vérifié à chaque appel).
  2. Cross-tenant detection: refus poli + log + alerte sécurité (Sentry tag).
  3. Budget mensuel par tenant + clés API par tenant configurables (`CompanySettings` étendu).
  4. Provider strategy = Anthropic prioritaire, fallback selon clés saisies pour le tenant. Cascade conservée.
- Schema: ajouter `CompanySettings.aiKeysAnthropic`, `aiKeysOpenAi`, `aiKeysGemini` (chiffrés au repos), `aiMonthlyBudget` (cents EUR).
- Compteur usage: `AiUsageLog` entity (companyId, period, tokens, costCents) — nouvelle table.
- Tests sécurité obligatoires: cross-tenant prompt, missing tenant context, budget exhausted.

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
