# US-063 — Chatbot AI multi-tenant avec garde-fous

> **BC**: AN  |  **Source**: archived AN.md (split 2026-05-11)

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

