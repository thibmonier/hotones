# Gate INVEST Backlog Validation — HotOnes (v2)

```
═══════════════════════════════════════════════════════════════
          Validation Gate INVEST Backlog (v2)
═══════════════════════════════════════════════════════════════
| Date:       2026-05-04 (post atelier business)              |
| Source:     project-management/backlog/user-stories/*.md     |
| Stories:    85 actives (1 merged, 1 nouvelle vs v1)          |
| Threshold:  6/6 INVEST par story                             |
═══════════════════════════════════════════════════════════════
```

> **v2** = re-validation après ateliers Q1–Q13 décidés. Tous les warns v1 résolus, US-077 fusionnée dans US-012, US-086 ajoutée.

---

## Résultats globaux

| Status | v1 | v2 |
|--------|---:|---:|
| ✅ Pass (6/6) | 78 | **85** |
| ⚠️ Warn (5/6) | 7 | **0** |
| ❌ Fail (<4/6) | 0 | 0 |
| **Total validé** | 85 | **85** |

**Verdict**: ✅ **GATE BACKLOG PASSÉ** — 85/85 stories à 6/6 INVEST.

---

## Audit critère par critère (v2)

| Critère | Conformes | Évolution |
|---------|----------:|-----------|
| **I** Independent | 85 / 85 | +6 (overlaps US-012/077 fusionnés, US-013/072 et US-014/061 confirmés indépendants) |
| **N** Negotiable | 85 / 85 | stable |
| **V** Valuable | 85 / 85 | stable |
| **E** Estimable | 85 / 85 | +7 (US-022/024/031/062/063/084/085 désormais explicités) |
| **S** Sized ≤ 8 pts | 85 / 85 | stable (16 stories à 8 pts limite — split candidats au planning poker) |
| **T** Testable | 85 / 85 | stable |

---

## Stories précédemment en warn — résolution

| Story | Critère manquant v1 | Résolution v2 | Status |
|-------|---------------------|---------------|--------|
| US-022 | E (formule TBD) | Composite 25/25/25/25 + healthLevel mapping documenté | ✅ |
| US-024 | E (entité Risk?) | 5 signaux scoring (budget %, glissement, marge, satisfaction, deps) — pas d'entité dédiée, calcul service | ✅ |
| US-031 | E (algo TBD) | Heuristique somme jours×proba/capacité, seuils 80/100, horizons 3/6/12m | ✅ |
| US-062 | E (méthode TBD) | MA 6 mois × pondération devis, probabilités OrderStatus explicites | ✅ |
| US-063 | E (scope tools/garde-fous) | 4 garde-fous: tenant filter mandatory, refus+log+alerte, budget mensuel + clés API par tenant, fallback Anthropic→OpenAI→Gemini | ✅ |
| US-084 | E (backend?) | MariaDB FULLTEXT + index sur 6+ colonnes | ✅ |
| US-085 | I (overlap cascading?) | US-085 = validation atomique, US-086 = cascading (séparation explicite) | ✅ |

---

## Stories à la limite S = 8 pts (candidats split planning poker)

Aucune ne fait fail mais à challenger en planning poker.

| Story | Module | Sujet | Recommandation découpage atelier |
|-------|--------|-------|----------------------------------|
| US-005 | IAM | Multi-tenant isolation | (a) TenantContext+SQLFilter, (b) backfill entités, (c) tests régression |
| US-012 | CRM | Lead funnel (post-fusion) | (a) capture publique + email opt-in, (b) pipeline backend qualification, (c) conversion lead→Client |
| US-013 | CRM | HubSpot sync workflow | (a) sync leads, (b) sync deals, (c) retry+circuit-breaker |
| US-015 | ORD | Cycle vie devis | (a) state-machine guard, (b) transitions WON/LOST/STANDBY/ABANDONED, (c) SIGNED→COMPLETED |
| US-016 | ORD | Composer devis | (a) sections, (b) lines+TVA, (c) tasks→ProjectTask conversion |
| US-020 | PRJ | Projets+sous-tâches | (a) Project CRUD, (b) Task tree, (c) close-guard |
| US-022 | PRJ | Score santé projet | (a) 4 sous-scores, (b) composite + mapping, (c) historique + alertes |
| US-029 | PLN | Planning capacité | (a) CRUD planning, (b) optimization, (c) conflicts congés |
| US-031 | PLN | Prédiction charge | (a) algo + tests, (b) endpoint UI, (c) cache |
| US-061 | AN | Dashboards multi-roles | (a) framework dashboard, (b) Sales+HR, (c) Staffing+Treasury+Project-health |
| US-062 | AN | Forecasting CA | (a) MA pipeline FactForecast, (b) endpoint+chart, (c) accuracy tracking |
| US-063 | AN | Chatbot AI multi-tenant | (a) Tools tenant-filter, (b) clés tenant + budget, (c) garde-fous + tests sécurité |
| US-072 | INT | HubSpot integration tech | (a) settings+credentials, (b) connecteur+test connexion, (c) retry/circuit |
| US-073 | INT | BoondManager | (a) settings, (b) sync staffing, (c) résolution conflits |

---

## Verdict

```
═══════════════════════════════════════════════════════════════
| ✅ GATE BACKLOG PASSÉ                                         |
| - 85 / 85 stories à 6/6 INVEST                               |
| - 0 warn, 0 fail                                             |
| - 1 fusion (US-077 → US-012)                                 |
| - 1 nouvelle story (US-086 cascading)                        |
| - Total 391 pts                                              |
═══════════════════════════════════════════════════════════════
```

### Actions sprint-006 (kickoff 2026-05-15)

1. **Planning poker** sur 14 stories à 8 pts (cf section ci-dessus) — split selon découpage proposé.
2. **PRD update**: ajouter FR-OPS-08, fusionner FR-CRM-03 + FR-MKT-03, retirer ROLE_COMMERCIAL ou wirer (cf gap-analysis #4).
3. **Schéma DB**: nouvelles colonnes `Order.winProbability`, `CompanySettings.aiKeys*` + `aiMonthlyBudget`, nouvelle entité `AiUsageLog`. Migrations Doctrine.
4. **Index FULLTEXT**: migration MariaDB sur 6+ colonnes (cf US-084).

### Critère de promotion en sprint actif

- [x] Toutes stories à 6/6 INVEST
- [x] FR/US 1:1 mapping
- [x] Overlaps arbitrés
- [ ] Planning poker sprint-006 fait (PO + Tech Lead + dev)
- [ ] Dépendances graphées (`/project:dependencies`)
- [ ] Critical path identifié (`/project:critical-path`)

---

## Confidence (v2)

| Aspect | Confidence |
|--------|------------|
| Comptage 85 stories | HIGH |
| Présence Card/Gherkin/Estimate | HIGH |
| Sized ≤ 8 | HIGH |
| E criterion (post atelier) | HIGH |
| I overlaps résolus | HIGH |
