# Sprint Review — Sprint 025 (EPIC-003 Phase 5 KPIs + Sub-epic D dette)

| Attribut | Valeur |
|---|---|
| Date | 2026-05-15 (clôture anticipée — window officielle 2026-06-24) |
| Sprint Goal | EPIC-003 Phase 5 : 2 nouveaux KPIs business + extension drill-down/CSV widgets DSO/lead time + solde dette Sub-epic D |
| Engagement ferme | 12 pts |
| Livré | **12/12 pts (100 %)** |

---

## 🎯 Atteinte du Sprint Goal — ✅ 100 %

- ✅ 2 nouveaux KPIs business (US-114 Revenue forecast + US-115 Conversion rate)
- ✅ Extension drill-down par client + export CSV (US-116)
- ✅ Sub-epic D dette soldée (Mago + VacationRepo + Coverage carry-over sp-022/023/024)
- ✅ Pattern KpiCalculator 6ᵉ application consécutive
- 🟡 Cap libre PRE-5/A-2 non assigné (3ᵉ sprint consécutif — héritage)

## 📦 Stories livrées

| ID | Titre | Pts | PR |
|---|---|---:|---|
| US-114 | KPI Revenue forecast | 3 | #282 |
| US-115 | KPI Taux conversion devis → commande | 3 | #284 (rebased) |
| US-116 | Extension widgets DSO/lead time (drill-down + CSV) | 2 | #286 |
| MAGO-LINT-BATCH-001 | Mago lint cleanup batch | 2 | #285 |
| VACATION-REPO-AUDIT | Audit Deptrac VacationRepository | 1 | #287 |
| TEST-COVERAGE-013 | Coverage audit + 24 tests Domain | 1 | #288 |

**Total : 12/12 pts (100 %).**

## 📈 Métriques

| Métrique | Valeur | Tendance vs sp-024 |
|---|---|---|
| Points planifiés ferme | 12 | = (5ᵉ confirmation baseline) |
| Points livrés | 12 | = |
| Engagement ratio | 1.00 | = (4 sprints consécutifs ≥ 1.00) |
| Vélocité moyenne 16 sp | ~11.13 | ↗️ |
| Stories complètes | 6/6 (100 %) | ↗️ |
| Tâches done | 23/23 (100 %) | = |
| PRs mergées | 6 | ↘️ vs sp-024 (28) — scope plus compact |
| Tests ajoutés | ~70 (Unit + Integration + Functional) | ↗️ |
| Mago issues | 5417 → 1307 | −4110 (−75 %) |
| Mago errors | 643 → 631 | −12 |
| Deptrac errors | 1 → 0 | ✅ |
| Holdover OPS | 0 | ✅ 5ᵉ sprint consécutif |
| `--no-verify` commits | 0 | ✅ 5ᵉ sprint consécutif |

## 🎬 Démonstration (35 min)

### 1. US-114 Revenue forecast (8 min)
- Widget `/admin/business-dashboard` : forecast 30/90 j + décomposition confirmé/pondéré
- Alerte Slack CRITICAL si forecast 30j < 5 000 €
- Architecture : `RevenueForecastCalculator` (Domain pure) + `PipelineOrderRecord` + adapter Doctrine + migration `idx_order_valid_until` + cache decorator + 2 subscribers invalidation (`OrderStatusChanged` + `InvoiceCreated`)

### 2. US-115 Conversion rate (8 min)
- Widget : taux 30/90/365 + tendance ↗️ ↘️ → + décomposition signés / émis (hors standby/termine/a_signer)
- Alerte Slack CRITICAL si rate 30j < 25 %
- Architecture : `ConversionRateCalculator` + VO `ConversionRate` + `ConversionTrend` enum

### 3. US-116 Drill-down + CSV (5 min)
- Bouton « Drill-down par client → » depuis widgets DSO + lead time
- Vue triée valeur décroissante + sélecteur fenêtre 30/90/365
- Export CSV `/.../drill-down/{kpi}/export`
- Architecture : extension `findAllClientsAggregated` sur read-models + `KpiDrillDownCsvExporter`

### 4. MAGO-LINT-BATCH-001 (5 min)
- `make mago` → vert (1307 filtered out)
- `mago-baseline.json` 128 KB pour résiduel cleanup progressif
- 3 régressions legacy fixées (strict-assertions mismatch types)

### 5. VACATION-REPO-AUDIT (4 min)
- `make deptrac` → errors 1 → 0
- Entry stale `App\Entity\Vacation` supprimée (déjà migré BC DDD)

### 6. TEST-COVERAGE-013 (5 min)
- 24 tests Domain (`WorkItemMigrationResult` invariants ADR-0013 cas 3 + `MigrationDriftDetail` + `MarginAdoptionStats` + `BillingLeadTimeStats`)
- `docs/coverage-audit-sprint-025.md` (audit + backlog sp-026+)

## 💬 Feedback à collecter

Questions PO :
1. Coef proba forecast 0.3 (US-114) — ajuster post-feedback société ?
2. Seuils warning/red US-114 (10k/5k €) et US-115 (40 %/25 %) — alignés vision business ?
3. Drill-down US-116 : pagination/limite si volume client > 50 ?
4. US-117 KPI Marge moyenne portefeuille (3 pts reporté) — priorité sprint-026 ?
5. Cap libre 1-2 pts — décision concrète sprint-026 J0 (4ᵉ fois TBD sinon) ?

Questions stakeholders :
1. Dashboard 8 KPIs (3 sp-024 + 2 sp-025 + 3 KPIs historiques) — ordre lisible ?
2. Slack channel `#kpi-alerts-prod` (action A-7 héritée sp-024) — toujours pending.

## 🚀 Achievements notables

### Pattern KpiCalculator 6ᵉ consécutive
- sp-024 : DSO + lead time + adoption + WorkItem migrator (4×)
- sp-025 : Revenue forecast + Conversion rate (2×)
- 6-task per KPI ~11-12 h pour 3 pts
- ROI estimation parfaite story-par-story

### Dette Sub-epic D — solde 3 items carry-over
- MAGO : −4110 issues (3 sprints reportés)
- VACREPO : Deptrac errors 0 (sp-023 retro L-4 hérité)
- COVERAGE-013 : 24 tests Domain (PRE-2 héritage)

### Mécanisme rebase stack PR adjacent appliqué
- #282 (US-114) + #284 (US-115) sur fichiers partagés (services.yaml + Controller + dashboard.html.twig)
- Procédure : merge US-114 d'abord → rebase US-115 sur main MAJ → force-push → merge (action héritée sp-024 retro A-3, documentée CONTRIBUTING.md sp-025)

## 📝 Impact sur le Backlog

| Action | Story | Description |
|---|---|---|
| ✅ Done | US-114/115/116 | EPIC-003 Phase 5 — 2 KPIs + drill-down |
| ✅ Done | MAGO/VACREPO/COV-013 | Sub-epic D dette soldée |
| 📋 Reporté | US-117 KPI Marge portefeuille | Backlog Phase 5 → sprint-026+ |
| 📋 Backlog | Mago résiduel 1307 | Cleanup progressif (CONTRIBUTING.md) |
| 📋 Backlog | Coverage push | 5 services legacy candidats sp-026+ |

## ⚠️ Risques résiduels

| Risque | Mitigation |
|---|---|
| Coef proba forecast US-114 arbitraire (0.3) | Configurable hiérarchique US-108 |
| Volume drill-down US-116 grand | Pagination sp-026+ si feedback |
| Mago résiduel 1307 | Baseline activé, cleanup progressif documenté |
| Cap libre TBD 4ᵉ fois potentiel | A-2 priorité High héritée |

## 📅 Prochaines étapes

1. **2026-05-15** : Sprint review formelle (cette doc)
2. **2026-05-15** : Sprint retro (`sprint-retro.md`)
3. **Sprint-026** : Atelier OPS-PREP-J0 + Planning P1 — décision scope (US-117 ? nouveau EPIC ? continuation Phase 5 ?)

## 📦 Livrables

- 6 PRs mergées (#282, #284, #285, #286, #287, #288)
- 6 stories complètes (4 features + 2 dette)
- 2 nouveaux widgets KPI (dashboard 8 KPIs total)
- 1 controller drill-down + export CSV
- 1 baseline Mago (1307 issues filtrées)
- 1 section CONTRIBUTING.md Mago lint + rebase stack
- 1 doc audit coverage
- ~70 tests ajoutés
- ~3500 lignes de code

## 🔗 Liens

- Sprint goal : `sprint-goal.md`
- Sprint-024 review : `../sprint-024-epic-003-phase-4-kickoff/sprint-review.md`
- Tasks : `tasks/README.md`
- Coverage audit : `../../../docs/coverage-audit-sprint-025.md`

---

**Auteur** : Tech Lead
**Date** : 2026-05-15
**Version** : 1.0.0
