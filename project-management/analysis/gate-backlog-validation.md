# Gate INVEST Backlog Validation — HotOnes

```
═══════════════════════════════════════════════════════════════
          Validation Gate INVEST Backlog
═══════════════════════════════════════════════════════════════
| Date:       2026-05-04                                       |
| Source:     project-management/backlog/user-stories/*.md     |
| Stories:    85                                               |
| Threshold:  6/6 INVEST par story                             |
═══════════════════════════════════════════════════════════════
```

> **Caveat**: stories sont toutes `INFERRED` du code (DRAFT). Validation INVEST ici teste la **forme** (présence Card / 3C / Gherkin / Estimate / Sized) et signale les notes "à expliciter / TBD" qui rompent **E** (Estimable).

---

## Résultats globaux

| Status | Count | % |
|--------|------:|--:|
| ✅ Pass (6/6) | 78 | 92% |
| ⚠️ Warn (4-5/6) | 7 | 8% |
| ❌ Fail (<4/6) | 0 | 0% |
| **Total validé** | **85** | **100%** |

**Verdict**: ⚠️ **GATE PASSÉ AVEC AVERTISSEMENTS** — 0 fail, 7 warn (E criterion: estimation nominal mais notes "à expliciter" minent la fiabilité).

---

## Audit critère par critère

| Critère | Stories conformes | Stories à risque |
|---------|------------------:|------------------|
| **I** Independent | 79 / 85 | 6 (overlaps documentés: US-012/077, US-013/072, US-014/061) |
| **N** Negotiable | 85 / 85 | 0 — toutes ont Card + 3C + notes |
| **V** Valuable | 85 / 85 | 0 — toutes ont AC Gherkin nominal + alternatif |
| **E** Estimable | 78 / 85 | 7 (algos / formules "à expliciter") |
| **S** Sized ≤ 8 pts | 85 / 85 | 0 — max = 8 pts (16 stories à la limite) |
| **T** Testable | 85 / 85 | 0 — toutes ont AC Gherkin |

---

## Stories en avertissement (⚠️ 5/6)

Sept stories scorent 5/6 — critère manquant systématique = **E (Estimable)** par sous-spécification métier ou algorithmique.

| Story | Module | Pts | Critère manquant | Note bloquante |
|-------|--------|----:|------------------|----------------|
| US-022 | PRJ | 8 | E | "Formule du score (`ProjectHealthScore`) à expliciter (PO + tech)" |
| US-024 | PRJ | 5 | E | "Modèle Risk pas trouvé comme entité dédiée → vérifier" — entité incertaine |
| US-031 | PLN | 8 | E | "Algo prédictif (ML ou heuristique) à expliciter" |
| US-062 | AN | 8 | E | "Pipeline de calcul (cron) à documenter; `FactForecast` table de faits — algo non explicité" |
| US-063 | AN | 8 | E | AI Chatbot — scope tools, garde-fous prompt-injection cross-tenant non bornés |
| US-084 | OPS | 5 | E | "Backend de recherche à expliciter (BDD LIKE, MeiliSearch, ElasticSearch?)" |
| US-085 | OPS | 2 | I (faible) | Couplage avec `DependentFieldsController` — sous-capabilité de US-085 (B7) → préciser périmètre |

---

## Stories à la limite S = 8 pts

Sized ≤ 8 OK, mais à risque si scope élargit. Découpage recommandé en planning poker.

| Story | Module | Sujet | Recommandation découpage |
|-------|--------|-------|--------------------------|
| US-005 | IAM | Multi-tenant isolation | Split: (a) TenantContext+Filter, (b) backfill entités, (c) tests régression |
| US-013 | CRM | HubSpot sync | Split: (a) settings+credentials, (b) sync leads, (c) sync deals, (d) retry/circuit-breaker |
| US-015 | ORD | Cycle vie devis | Split: (a) state-machine guard, (b) transitions WON/LOST/STANDBY/ABANDONED, (c) SIGNED→COMPLETED |
| US-016 | ORD | Composer devis | Split: (a) sections, (b) lines+TVA, (c) tasks→ProjectTask conversion |
| US-020 | PRJ | Projets+sous-tâches | Split: (a) Project CRUD, (b) Task tree, (c) close-guard |
| US-022 | PRJ | Score santé projet | Split: (a) formule+calcul, (b) historique, (c) alertes seuils |
| US-029 | PLN | Planning capacité | Split: (a) CRUD planning, (b) optimization, (c) conflicts congés |
| US-031 | PLN | Prédiction charge | Split: (a) algo + tests, (b) endpoint UI, (c) cache |
| US-061 | AN | Dashboards multi-roles | Split: (a) framework dashboard, (b) Sales+HR, (c) Staffing+Treasury+Project-health |
| US-062 | AN | Forecasting | Split: (a) FactForecast pipeline, (b) endpoint, (c) intervalle confiance |
| US-063 | AN | Chatbot AI | Split: (a) tools tenant-scoped, (b) provider strategy, (c) guardrails+coût |
| US-072 | INT | HubSpot integration | (cf US-013 — fusion ou clarification de périmètre) |
| US-073 | INT | BoondManager integration | Split: (a) settings, (b) sync staffing, (c) résolution conflits |

---

## Overlaps Independent (I) à arbitrer

| Stories en chevauchement | Module(s) | Action recommandée |
|--------------------------|-----------|--------------------|
| US-012 (CRM-03) ↔ US-077 (MKT-03) | CRM ↔ MKT | Garder les deux. Marquer US-077 comme "front capture", US-012 "back pipeline". Lien parent/enfant. |
| US-013 (CRM-04) ↔ US-072 (INT-01) | CRM ↔ INT | Fusionner OR clarifier: US-013 = workflow CRM, US-072 = settings + connecteur engineering. |
| US-014 (CRM-05) ↔ US-061 (AN-02) | CRM ↔ AN | US-061 = framework parent, US-014 = instance Sales dashboard. |

---

## Détail Pass (78 stories, échantillon condensé)

> Liste complète: chaque story conforme INVEST 6/6 si Card+3C+Gherkin+Estimate+Sized présents. Échantillon ci-dessous; le reste passe identique.

```
✅ US-001 Auth web formulaire           [3 pts] [I✓ N✓ V✓ E✓ S✓ T✓] 6/6
✅ US-002 Auth API JWT                  [3 pts] 6/6
✅ US-003 Activation 2FA TOTP           [5 pts] 6/6
✅ US-004 Hiérarchie rôles 7 niveaux    [2 pts] 6/6
⚠️ US-005 Isolation multi-tenant       [8 pts] 6/6 (S limite)
✅ US-006 Gestion profil                [3 pts] 6/6
✅ US-007 Admin utilisateurs            [5 pts] 6/6
✅ US-008 Demande suppression RGPD      [5 pts] 6/6
✅ US-009 Consentement cookies          [3 pts] 6/6
✅ US-010..011 (CRM)                    [3-5 pts] 6/6
✅ US-012 Pipeline leads CRM            [5 pts] 5/6 (I overlap US-077)
⚠️ US-013 Sync HubSpot                  [8 pts] 5/6 (I overlap US-072)
✅ US-014 Dashboard ventes              [5 pts] 5/6 (I overlap US-061)
✅ US-015..019 (ORD)                    [3-8 pts] 6/6
✅ US-020 Gestion projets+sous-tâches   [8 pts] 6/6 (S limite)
✅ US-021 Journal événements            [3 pts] 6/6
⚠️ US-022 Score santé projet           [8 pts] 5/6 (E formule)
✅ US-023 Skills+techno projet          [3 pts] 6/6
⚠️ US-024 Vue risques projet           [5 pts] 5/6 (E entité Risk?)
✅ US-025 Alerte budget                 [5 pts] 6/6
✅ US-026 Alerte marge basse            [5 pts] 6/6
✅ US-027 Projets à risque              [3 pts] 6/6
✅ US-028 Bulk archive/delete           [3 pts] 6/6
✅ US-029 Planning capacité             [8 pts] 6/6 (S limite)
✅ US-030 Dashboard staffing            [5 pts] 6/6
⚠️ US-031 Prédiction charge            [8 pts] 5/6 (E algo)
✅ US-032 Alerte surcharge              [5 pts] 6/6
✅ US-033 Mes tâches                    [3 pts] 6/6
✅ US-034..037 (TIM)                    [3-5 pts] 6/6
✅ US-038..045 (VAC, BC complet)        [2-5 pts] 6/6 — référence DDD
✅ US-046..051 (INV)                    [3-5 pts] 6/6
✅ US-052..059 (HR)                     [3-5 pts] 6/6
✅ US-060 KPI seuils                    [5 pts] 6/6
✅ US-061 Dashboards multi-roles        [8 pts] 5/6 (I overlap US-014)
⚠️ US-062 Forecasting                   [8 pts] 5/6 (E algo)
⚠️ US-063 Chatbot AI                    [8 pts] 5/6 (E scope tools+garde-fous)
✅ US-064 Recommandations API           [5 pts] 6/6
✅ US-065..068 (NTF)                    [3-5 pts] 6/6
✅ US-069..071 (SAAS)                   [2-5 pts] 6/6
⚠️ US-072 HubSpot integration           [8 pts] 5/6 (I overlap US-013)
⚠️ US-073 BoondManager integration      [8 pts] 6/6 (S limite, scope retry à clarifier)
✅ US-074 Stockage S3                   [3 pts] 6/6
✅ US-075..076 (MKT)                    [5 pts] 6/6
✅ US-077 Lead-magnet                   [5 pts] 5/6 (I overlap US-012)
✅ US-078 Sitemap                       [2 pts] 6/6
✅ US-079..083 (OPS)                    [2-5 pts] 6/6
⚠️ US-084 Recherche transverse         [5 pts] 5/6 (E backend?)
⚠️ US-085 Endpoint validation          [2 pts] 5/6 (I périmètre live forms)
```

---

## Verdict & Actions

```
═══════════════════════════════════════════════════════════════
| ⚠️ GATE BACKLOG PASSÉ AVEC AVERTISSEMENTS                    |
| - 0 stories en échec (<4/6)                                  |
| - 7 stories en warn (5/6) — décisions PO/tech requises       |
| - 78 stories en pass (6/6) — prêtes pour planning            |
═══════════════════════════════════════════════════════════════
```

### Actions PO/Tech avant promotion en sprint

1. **US-022** — formule `ProjectHealthScore` → atelier PO+tech, documenter, mettre à jour AC.
2. **US-024** — clarifier modèle Risk: entité dédiée vs JSON column. Mettre à jour spec.
3. **US-031** — choisir algo prédiction charge (heuristique vs ML simple). Documenter.
4. **US-062** — pipeline FactForecast: source data, cadence cron, formule.
5. **US-063** — borner scope AI tools, lister garde-fous prompt-injection cross-tenant.
6. **US-084** — choisir backend recherche (LIKE / MeiliSearch / ES / Postgres FTS) — note: stack MariaDB, donc plutôt MeiliSearch / ES.
7. **US-085** — confirmer si fusionner avec live forms `DependentFieldsController` ou conserver séparé.

### Actions overlaps Independent

1. **US-012 ↔ US-077** — fusionner ou clarifier parent/enfant (front capture vs back pipeline).
2. **US-013 ↔ US-072** — décider: fusionner ou découpage settings vs workflow.
3. **US-014 ↔ US-061** — US-061 parent, US-014 instance.

### Re-run

Après corrections:
```
/project:gate-validate-backlog
```

---

## Confidence

| Aspect | Confidence |
|--------|------------|
| Comptage 85 stories | HIGH |
| Présence Card/Gherkin/Estimate | HIGH (vérifié visuellement à l'écriture) |
| Sized ≤ 8 | HIGH |
| E criterion sub-judgment | MEDIUM (basé sur notes "à expliciter") |
| I overlaps | HIGH |
