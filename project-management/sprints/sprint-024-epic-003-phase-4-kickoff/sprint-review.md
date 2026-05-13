# Sprint Review — Sprint 024 (EPIC-003 Phase 4 KPIs business)

| Attribut | Valeur |
|---|---|
| Date | 2026-05-13 (anticipée vs window officielle 2026-06-10) |
| Durée prévue | 2 h |
| Sprint Goal | « EPIC-003 Phase 4 kickoff : KPIs business (DSO + temps facturation + adoption marge temps réel). Démarrage US-110..US-112. Décision PO PRE-5 Render redeploy. » |
| Engagement ferme | 12 pts |
| Livré | **12/12 pts (100 %)** |
| Animateur | Tech Lead |

---

## 🎯 Atteinte du Sprint Goal

> **Sprint Goal atteint : ✅ OUI — 100 %**

Justification :
- ✅ EPIC-003 Phase 4 démarrée — 4 stories KPI/migration livrées
- ✅ Décision PRE-4 Render redeploy actée (ADR-0018) — atelier OPS-PREP-J0 conduit J-16 anticipé
- ✅ KPIs business opérationnels sur `/admin/business-dashboard` (DSO + lead time + adoption marge)
- ✅ Migration WorkItem.cost legacy livrée avec runbook prod + tests E2E + dry-run safety
- 🟡 Cap libre PRE-6 (1-2 pts) reste à décider Sprint Planning P1 2026-05-27 — non-bloquant

## 📦 User Stories livrées

| ID | Titre | Pts | Demo | Status |
|---|---|---:|---|---|
| OPS-PRE5-DECISION | ADR-0018 Render redeploy Option A | 1 | ✅ smoke `/health` JSON valide | ✅ Livré |
| US-110 | KPI DSO (Days Sales Outstanding) | 3 | ✅ widget dashboard + Slack | ✅ Livré |
| US-111 | KPI temps de facturation | 3 | ✅ widget + top 3 clients + Slack | ✅ Livré |
| US-112 | KPI adoption marge temps réel | 2 | ✅ widget bar chart + Slack 7j | ✅ Livré |
| US-113 | Migration WorkItem.cost legacy | 3 | ✅ dry-run + CSV + runbook | ✅ Livré |

**Total livré : 12/12 pts (100 %).**

## 📈 Métriques

| Métrique | Valeur | Tendance vs sp-023 |
|---|---|---|
| Points planifiés ferme | 12 | = (4ᵉ confirmation baseline) |
| Points livrés | 12 | = |
| Engagement ratio | 1.00 | = sp-023 (sp-022 1.08, sp-021 1.17) |
| Vélocité | 12 | = (moy 14 sprints = 11) |
| Stories complète | 5/5 | ↗️ 100 % |
| Tasks done | 28/29 | T-113-07 user-tracked hors sprint |
| PRs mergées | 28 (#236 → #266) | ↗️ vs sp-023 (~10) |
| Tests added | ~120 (Unit + Integration + Functional) | ↗️ |
| Lines added | ~5500 | ↗️ |
| Holdover OPS | 0 | ↘️ (4ᵉ sprint consécutif 0) ✅ |
| `--no-verify` commits | 0 | = 0 |
| Vélocité moy 14 sp | 11.07 | ↗️ |

## 🎬 Démonstration

### 1. OPS-PRE5-DECISION — Render redeploy (5 min)
- **Demo** : `curl https://hotones.onrender.com/health` → JSON `{"status":"healthy"...}`
- Image stale 2026-01-12 → fresh 2026-05-12
- 6ᵉ sprint holdover clôturé (réversibilité ADR-0017 → ADR-0018)
- **Démo par** : Tech Lead

### 2. US-110 KPI DSO (8 min)
- **Demo** : `/admin/business-dashboard` widget DSO
  - 30/90/365 jours rolling + tendance ↗️/↘️/→
  - Badge warning si DSO > 45j (seuil orange)
  - Slack CRITICAL si DSO > 60j (red threshold)
- **Architecture** : DsoCalculator + DsoReadModelRepository + Caching decorator + Subscriber InvoicePaidEvent
- **Démo par** : Tech Lead

### 3. US-111 KPI temps facturation (8 min)
- **Demo** : widget Twig
  - Médiane / p75 / p95 par fenêtre 30/90/365
  - Top 3 clients lents (numbered list + avg + count)
  - Slack CRITICAL si médiane 30j > 30j
- **Architecture** : BillingLeadTimeCalculator (percentile NIST type 7) + Doctrine JOIN Invoice↔Order↔Client
- **Démo par** : Tech Lead

### 4. US-112 KPI adoption marge (5 min)
- **Demo** : widget bar chart stacked Bootstrap
  - % fresh < 7 j (central + couleur)
  - 3 segments visualisés (Frais / Tiède / Stale)
  - Slack CRITICAL si adoption < 40 % sur 7 jours consécutifs (persistance cache.kpi)
- **Architecture** : MarginAdoptionCalculator + AlertState VO + Symfony Command cron
- **Démo par** : Tech Lead

### 5. US-113 Migration WorkItem.cost (8 min)
- **Demo** :
  - `bin/console app:workitem:migrate-legacy-cost --dry-run --csv-report=auto`
  - Output table résumé (mode, processed, drifts, ratio)
  - Trigger abandon ADR-0013 cas 3 (exit code 1 si drift > 5 %)
  - Runbook prod `docs/runbooks/workitem-cost-migration.md`
- **Architecture** : WorkItemMigrator (Domain pure) + Doctrine adapters + DriftReportCsvExporter
- **Démo par** : Tech Lead

## 💬 Feedback à collecter

Questions PO :
1. Les seuils DSO 45j (warning) / 60j (red) sont-ils alignés avec votre vision tréso ?
2. Top 3 clients lents : suffisant ou besoin top 5/10 ?
3. Adoption marge 7 jours consécutifs avant alerte : pas trop tardif ?
4. Migration WorkItem.cost : window maintenance prod préférée (J+0 vs sprint-025 dédié) ?
5. PRE-6 cap libre 1-2 pts : Mago lint cleanup / VacationRepo audit / TEST-COVERAGE-013 ?

Questions stakeholders :
1. Dashboard 3 widgets KPI : ordre actuel (DSO → lead time → adoption) OK ?
2. Slack channel `#alerts-prod` : volume alertes prévisible ou besoin canal dédié KPI ?

## 🚀 Achievements notables

### Pattern KpiCalculator établi (réutilisable futurs sprints)

```
Pattern 6-task per story (~11h pour 3 pts) :
  1. Domain Service Calculator (pure PHP)
  2. Repository read-model port + Doctrine adapter
  3. Cache decorator + Event subscriber invalidation
  4. Widget Twig + Handler CQRS
  5. Slack alert subscriber
  6. Integration E2E tests
```

→ Réplicable directement sur KPIs futurs sans réinvention archi.

### Trigger réversibilité ADR-0017 validé

- 4 holdovers consécutifs → out-backlog (ADR-0017 sprint-022)
- 5ᵉ sprint (sp-023) holdover hérité
- 6ᵉ sprint (sp-024) : décision PO + Tech Lead disponible J0 → réversibilité satisfaite → ADR-0018 redeploy
- Pattern formalise : `4 holdover → out` puis `out + decision-makers J0 → reopen`

### Hotfix post-merge conflict détecté + résolu

- PRs #244..#250 mergés en chaîne sans rebase intermédiaire → conflits silencieux sur services.yaml / Controller / template / test
- PR #253 fix : 4 fichiers restaurés, suite tests 192/192 (no regression)
- Procédure future : merger US-X stack complet AVANT rebaser stack US-Y

## 📝 Impact sur le Product Backlog

| Action | Story | Description |
|---|---|---|
| ✅ Done | US-110/111/112 | EPIC-003 Phase 4 KPIs business complets |
| ✅ Done | US-113 | Migration WorkItem.cost legacy + runbook prod |
| 🔄 Hors sprint | T-113-07 | Dry-run prod user-tracked (post sprint closure) |
| 📋 Backlog | PRE-6 candidats | Mago lint / VacationRepo audit / TEST-COVERAGE-013 |
| 📋 Sprint-025+ | EPIC-003 Phase 5 ? | Décision PO scoping post-review |

## ⚠️ Risques résiduels

| Risque | Mitigation |
|---|---|
| US-113 prod exec : backup BDD + fenêtre maintenance | Runbook section §2-§4 + T-113-07 user-tracked |
| Drift > 5 % en prod (cas 3 ADR-0013) | Procédure abandon §5 + décision PO obligatoire |
| Cache.kpi pool partagé US-110/111/112 → invalidation cascade | Acceptable (1h re-warm), doc design notes |
| Mago lint 626 errors stable | Sub-epic D candidate PRE-6 |

## 📅 Prochaines étapes

1. **2026-05-13** : Sprint review formelle (cette doc)
2. **2026-05-13 16:30** : Sprint retro (cf `/workflow:retro 024`)
3. **2026-05-26** : Atelier OPS-PREP-J0 sprint-025 (déjà conduit anticipé pour sp-024 J-16)
4. **2026-05-27** : Sprint Planning P1 sprint-025 + décision PRE-6 cap libre
5. **Post sprint-025+** : T-113-07 dry-run prod user-tracked + revue drift CSV → décision exec

## 📦 Livrables

- 28 PRs mergées (`#236` → `#266`)
- 5 stories complètes
- 3 widgets KPI sur `/admin/business-dashboard`
- 5 Slack alert listeners (DSO + billing + margin adoption)
- 1 Symfony command CLI (migrate-legacy-cost)
- 2 runbooks (`workitem-cost-migration.md` + atelier OPS-PREP-J0 minutes)
- 1 ADR (`0018-render-redeploy-option-a.md`)
- ~120 cas de tests ajoutés (Unit + Integration + Functional)
- ~5500 lignes de code

## 🔗 Liens

- Sprint-023 review : `../sprint-023-epic-003-phase-3-finition/sprint-review.md`
- Atelier OPS-PREP-J0 : `atelier-ops-prep-j0.md`
- Sprint goal : `sprint-goal.md`
- ADR-0013 (EPIC-003 scope)
- ADR-0017 (sub-epic B out backlog)
- ADR-0018 (Render redeploy Option A)
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- Runbook WorkItem migration : `../../../docs/runbooks/workitem-cost-migration.md`

---

**Auteur** : Tech Lead
**Date** : 2026-05-13
**Version** : 1.0.0
