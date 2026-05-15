# Sprint Retrospective — Sprint 025

| Sprint | 025 — EPIC-003 Phase 5 KPIs + Sub-epic D dette |
| Date | 2026-05-15 (clôture anticipée — window 2026-06-24) |
| Format | Starfish |
| Engagement | 12 pts ferme |
| Livré | 12 pts (100 %) |

## 🌟 Directive Fondamentale

> « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qui était connu à ce moment-là. » — Norm Kerth

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Baseline 12 pts ferme 5ᵉ confirmation consécutive** (sp-021..025). Vélocité durable. |
| K-2 | **Pattern KpiCalculator 6ᵉ application** (sp-024 4× + sp-025 US-114/115). Réplicable sans réinvention. |
| K-3 | **0 holdover OPS 5ᵉ sprint** + **0 commit `--no-verify` 5ᵉ sprint**. Discipline maintenue. |
| K-4 | **Sub-epic D dette intégrée dans sprint feature** (4 pts dette + 8 pts feature = 12 ferme). Pattern viable sans sprint dédié dette. |
| K-5 | **Procédure rebase stack PR adjacent validée en pratique** (#284 ↔ #282 conflits services.yaml + Controller + dashboard) + documentée CONTRIBUTING.md. |
| K-6 | **Audit-first dette technique** (MAGO + VACREPO + COVERAGE). Doc explicite + baseline cleanup progressif. |

### START

| # | Item |
|---|---|
| S-1 | **Verify full suite avant Mago `--potentially-unsafe`** — 3 régressions legacy `strict-assertions` détectées post-fix. Procédure : run full suite (pas unit seule) avant push auto-fix. |
| S-2 | **Helper `KpiTestSupport` trait** — pattern setUp commun (MultiTenant + ResetDatabase + cache.kpi clear + ProjectFactory) répété 4× ce sprint. Extraire trait. |
| S-3 | **Hook pre-commit : ajouter `make mago`** — empêcher nouvelles issues hors baseline. |
| S-4 | **Mago auto-fix segmenté par règle** (`--rule X` puis tests intermédiaires) au lieu de `--potentially-unsafe` global. |

### STOP

| # | Item |
|---|---|
| ST-1 | **Cap libre PRE-5/A-2 TBD 3ᵉ fois consécutive** (sp-023 ST-1 → sp-024 PRE-6 → sp-025). HIGH priority A-2 héritée 2 fois ignorée. Sprint Planning P1 sp-026 DOIT figer story concrète. |
| ST-2 | **Spec US-116 sous-spécifiée volume client** — pagination drill-down non scopée J0. Sprint-026 : forcer scoping volume dès Planning P1. |
| ST-3 | **Anon classes implements interface dans tests** — 6 anon classes ont fail (`findAllClientsAggregated` missing) après extension US-116. Préférer mocks PHPUnit `getOnlyMethods` plus tolérants à l'évolution interface. |

### MORE

| # | Item |
|---|---|
| M-1 | **Template 5-tests Integration E2E par KPI** (cache populé/invalidé + N events + Slack seuil + spam guard). Standardiser. |
| M-2 | **Pattern timestamping testabilité listeners** (event.occurredOn) — réutilisé US-114 + US-115. Documenter ADR. |
| M-3 | **Audit-first pour toute dette technique** — étendre pattern MAGO/VACREPO/COVERAGE à dette future. |

### LESS

| # | Item |
|---|---|
| L-1 | **Conflits services.yaml + Controller + dashboard sur stack PR adjacent** — 3 fichiers partagés #282 ↔ #284. Investiguer auto-discovery KPI handlers via tag/attribute Symfony. |
| L-2 | **PHP-CS-Fixer ↔ Mago overlap rules** — cs-fixer reformatte fichiers post-Mago dans même session. Aligner les 2 outils ou skip cs-fixer sur fichiers Mago-touched. |

---

## 🎯 Actions sprint-026

| ID | Action | Owner | Sprint | Priorité |
|---|---|---|---|---|
| A-1 | **Cap libre PRE-5 — story concrète J0 (4ᵉ fois TBD inacceptable)** | PO | sp-026 Planning P1 | **HIGH** |
| A-2 | Décision US-117 KPI Marge portefeuille — sp-026 ou backlog ? | PO | sp-026 Planning P1 | High |
| A-3 | Helper `KpiTestSupport` trait (Multi-tenant + cache + project setUp) | Tech Lead | sp-026 refactor 1h | Medium |
| A-4 | Hook pre-commit : ajouter `make mago` step | Tech Lead | sp-026 OPS | Medium |
| A-5 | ADR pattern timestamping testabilité listeners | Tech Lead | sp-026 doc-only | Low |
| A-6 | Procédure Mago segmentée par règle (CONTRIBUTING.md) | Tech Lead | sp-026 doc | Medium |
| A-7 | Décision Slack channel `#kpi-alerts-prod` (héritage 2 sprints) | PO + Tech Lead | sp-026 OPS-PREP | Low |
| A-8 | Pagination drill-down US-116 — décision volume seuil | PO | sp-026 Planning P1 | Low |

## Actions héritées sprint-024 retro — statut sp-025

| ID héritage | Action | Statut sp-025 |
|---|---|---|
| A-1 sp-024 | `enablePullRequestAutoMerge` repo settings | ❌ Non fait — re-héritage |
| A-2 sp-024 | PRE-6 cap libre story concrète J0 | ❌ Non fait — A-1 sp-026 HIGH |
| A-3 sp-024 | Doc procédure rebase stack PR | ✅ Fait dans T-MAGO-03 |
| A-4 sp-024 | Helper `DateTime::mutableFromImmutable()` | 🟡 Pas utilisé sp-025 (DateTimeImmutable partout) — laisser backlog |
| A-5 sp-024 | T-113-07 dry-run prod WorkItem.cost | 🟡 Non exécuté — fenêtre maintenance à planifier |
| A-6 sp-024 | Doc cache.kpi pool partagé ADR | 🟡 Implicite via comments — formaliser ADR sp-026 |
| A-7 sp-024 | Slack channel `#kpi-alerts-prod` | ❌ Non fait — A-7 sp-026 |

## 📊 Métriques

| Métrique | sp-023 | sp-024 | sp-025 | Tendance |
|---|---|---|---|---|
| Engagement ferme | 12 | 12 | 12 | = (5ᵉ consécutif) |
| Livré | 12 | 12 | 12 | = |
| Engagement ratio | 1.00 | 1.00 | 1.00 | = |
| Holdover OPS | 0 | 0 | 0 | ✅ 5ᵉ |
| `--no-verify` | 0 | 0 | 0 | ✅ 5ᵉ |
| Mago issues | 626 | 626 | 1307 baseline | ✅ baseline activé |
| Deptrac errors | 0 | 0 | 0 | ✅ |
| PRs mergées | 8 | 28 | 6 | scope variable |

## 🚀 ROI sprint-025 (vs estimation)

| Item | Estimé | Livré | Δ |
|---|---|---|---|
| US-114 Revenue forecast | 3 pts (12h) | 3 pts (12h) | ✅ exact |
| US-115 Conversion rate | 3 pts (12h) | 3 pts (12h) | ✅ exact |
| US-116 Extension widgets | 2 pts (7h) | 2 pts (7h) | ✅ exact |
| MAGO-LINT-BATCH-001 | 2 pts (5h) | 2 pts (~3h) | ✅ −2h (auto-fix) |
| VACATION-REPO-AUDIT | 1 pt (3h) | 1 pt (~30min) | ✅ −2.5h (audit révèle déjà migré) |
| TEST-COVERAGE-013 | 1 pt (4h) | 1 pt (~3h) | ✅ −1h |
| **Total** | **12 pts (43h)** | **12 pts (~37h)** | ✅ **−14 % vs estim** |

Dette technique plus rapide que prévu : auto-fix Mago + audit Vacation pré-migré.

## 🔗 Liens

- Sprint review : `sprint-review.md`
- Sprint goal : `sprint-goal.md`
- Sprint-024 retro : `../sprint-024-epic-003-phase-4-kickoff/sprint-retro.md`
- Coverage audit : `../../../docs/coverage-audit-sprint-025.md`
- CONTRIBUTING.md (Mago + rebase stack) : `../../../CONTRIBUTING.md`

---

**Auteur** : Tech Lead
**Date** : 2026-05-15
**Version** : 1.0.0
