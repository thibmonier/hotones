# Sprint Retrospective — Sprint 024

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 024 — EPIC-003 Phase 4 KPIs business + Migration WorkItem.cost |
| Date | 2026-05-13 (clôture anticipée — J0..J+1 compactés vs window 2026-05-27 → 2026-06-10) |
| Format | Starfish |
| Engagement | 12 pts ferme |
| Livré | 12 pts (100 %) |

## 🌟 Directive Fondamentale de la Rétrospective

> « Indépendamment de ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qui était connu à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. »
> — Norm Kerth

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Recalibrage 12 pts ferme confirmé 4ᵉ sprint consécutif** (sp-021 117 % → sp-022 108 % → sp-023 100 % → sp-024 100 %). Vélocité durable, pilotage stabilisé. |
| K-2 | **0 holdover OPS sub-epic 4ᵉ sprint consécutif** — runbook OPS-PREP-J0 prouvé. ADR-0017 réversibilité validée (sp-024 trigger satisfait → ADR-0018 redeploy). Pattern complet : 4 holdover → out → reopen quand decision-makers J0 dispo. |
| K-3 | **Pattern KpiCalculator établi 4× consécutivement** (DSO + Billing lead time + Margin adoption + WorkItem migrator). 6-task per story (~11h pour 3 pts). Domain pure + Repository port + Cache decorator + Widget + Slack + Integration E2E. Réplicable sprints futurs sans réinvention. |
| K-4 | **TDD strict respecté sur 5 stories** : Calculator unit-first sur 4 calculators, Repository integration tests pour chaque DQL, Functional dashboard tests. Coverage Domain ~95 % sur calculators. |
| K-5 | **0 commit `--no-verify` sprint-024** — discipline maintenue 4ᵉ sprint consécutif. Pre-push Docker hook passé sur 28 PRs. |
| K-6 | **Atelier OPS-PREP-J0 conduit J-16 anticipé** (vs deadline 2026-05-26). Runbook §2 screening Q1–Q6 appliqué sur 5 stories. Décision PRE-4 actée → ADR-0018 + smoke `/health` JSON 2026-05-12. Pattern OPS prep crédible. |
| K-7 | **Sprint anticipé J-16 réussi 100 %** — exec accélérée multi-stories simultanées sans blocage. Démonstration faisabilité agentic mode sur stories KPI complexes. |

### START

| # | Item |
|---|---|
| S-1 | **Stack PR avec rebase intermédiaire entre stories** — sp-024 a démontré hotfix #253 nécessaire post-merge en chaîne #244 → #250 (conflits silencieux services.yaml + Controller + template + test). Procédure future : merger US-X stack complet AVANT de rebaser stack US-Y sur main MAJ. À documenter dans CONTRIBUTING.md. |
| S-2 | **Tests Integration sur Symfony Command + container override** — pattern `commandTesterWithFreshContainer()` introduit dans T-113-05. À extraire helper réutilisable sprint-025+ (autre commands). |
| S-3 | **Conversion `DateTimeImmutable → DateTime` Doctrine systématique** — pattern dupliqué US-110 T-110-02 InvoiceFactory + US-113 T-113-05 repository writes. Extraire VO helper ou Doctrine custom type `datetime_immutable_mutable_bridge` pour éviter repeat. |
| S-4 | **Cron registration manquante** — T-112-04 + T-113-03 introduisent commands `app:kpi:check-margin-adoption-threshold` + `app:workitem:migrate-legacy-cost`. Aucune config crontab/launchd checked-in. Sprint-025 candidate : `config/crontab.yaml` ou Symfony Scheduler bundle. |

### STOP

| # | Item |
|---|---|
| ST-1 | **Auto-merge GitHub désactivé sur repo** — sp-024 merges manuels (gh pr merge --squash) requis pour chaque PR. Sprint-025 candidate : activer `enablePullRequestAutoMerge` repo settings OU branch protection pour CI gating automatic. |
| ST-2 | **Cap libre PRE-6 non assigné J0** — 2ᵉ sprint consécutif (sp-023 ST-1 → sp-024 PRE-6 toujours TBD). Sprint Planning P1 sp-025 doit figer assignement story concrète AVANT kickoff (vs slot reserved vide). |
| ST-3 | **Spec US-113 "WorkItem.cost legacy"** terminologie ambiguë — vrai stockage = `timesheets.cost` derivé runtime via translator, pas champ persisté. Interprétation pragma (snapshot baseline) communiquée dans commit messages. Sprint-025+ : clarifier specs futures avec audit data path explicite. |

### MORE

| # | Item |
|---|---|
| M-1 | **Pattern Cache.kpi pool partagé** : US-110/111/112 invalidé même pool. Acceptable (1h re-warm) mais doc design notes explicite needed pour futur dev. À ajouter section dans `cache.yaml` ou ADR dédié si plus de KPIs ajoutés. |
| M-2 | **Slack alerts patterns** : 5 listeners créés sp-024 (DSO red, lead time red, margin adoption persistent). Volume estimé prod. Considérer channel dédié `#kpi-alerts-prod` pour éviter noise dans `#alerts-prod` (currently catch-all). |
| M-3 | **Domain Service autowire pattern** : 4 calculators autowire explicite (DsoCalculator, BillingLeadTimeCalculator, MarginAdoptionCalculator, WorkItemMigrator). À extraire `App\Domain\*\Service:` resource wildcard dans services.yaml pour automatiser. |

### LESS

| # | Item |
|---|---|
| L-1 | **Tests Integration Functional avec ProjectFactory::random() failure** — répété 2× sp-024 (T-111-02 + T-112-05 setUp). À documenter dans support test trait (`ensureProjectFactory()` helper) ou modifier `OrderFactory::defaults()` pour ne pas force ProjectFactory random. |
| L-2 | **Conflits merge silencieux Git** — patches additive Git ne détecte pas overlap sémantique services.yaml + template includes + test duplicate methods. Procédure rebase obligatoire AVANT merge stack adjacent (cf S-1). Investiguer pre-merge hook detecting duplicate method declarations. |

---

## 🎯 Actions résultant de la rétro

### Actions sprint-025

| ID | Action | Owner | Sprint | Priorité |
|---|---|---|---|---|
| A-1 | Activer GitHub `enablePullRequestAutoMerge` settings repo | Tech Lead | sprint-025 (J-2) | High |
| A-2 | Cap libre PRE-6 sp-025 : assignement story concrète J0 (vs slot reserved) | PO | sprint-025 Planning P1 | High |
| A-3 | Documenter procédure rebase stack PR adjacent dans CONTRIBUTING.md | Tech Lead | sprint-025 doc-only | Medium |
| A-4 | Extraire helper `DateTime::mutableFromImmutable()` dans Tests Support | Tech Lead | sprint-025 (refactor 30 min) | Medium |
| A-5 | T-113-07 dry-run prod user-tracked (post-merge sp-024 closure) | user + Tech Lead | sprint-025 J0 maintenance window | High |
| A-6 | Documenter cache.kpi pool partagé design notes (ADR ou cache.yaml comment) | Tech Lead | sprint-025 doc-only | Low |
| A-7 | Décision channel Slack dédié `#kpi-alerts-prod` vs `#alerts-prod` | PO + Tech Lead | sprint-025 atelier OPS-PREP | Low |

### Actions héritées sprint-023 retro

| ID héritage | Statut sp-024 |
|---|---|
| A-1 sp-023 Atelier OPS-PREP-J0 sp-024 J-2 | ✅ conduit J-16 anticipé (atelier-ops-prep-j0.md) |
| A-2 sp-023 Coverage post sp-023 | ✅ 70 % CI report |
| A-3 sp-023 Décision PO scope sp-024 | ✅ Phase 4 + Migration + OPS-PRE5 = 12 pts |
| A-4 sp-023 Décision PRE-5 Render redeploy | ✅ Option A actée (ADR-0018) |
| A-5 sp-023 Stories Gherkin US-110..US-113 | ✅ PR #237 |
| A-6 sp-023 Maintenir baseline 12 pts ferme | ✅ 4ᵉ confirmation 12/12 |
| A-7 sp-023 Pré-allocation cap libre 1-2 pts | 🟡 PRE-6 non décidé (héritage ST-2) |
| A-8 sp-023 Audit VacationRepository Deptrac | 🟡 Sub-epic D candidate, non exécuté |

## 📊 Métriques de qualité

| Métrique | sp-022 | sp-023 | sp-024 | Tendance |
|---|---|---|---|---|
| Engagement ferme | 12 | 12 | 12 | = stable |
| Livré | 13 | 12 | 12 | = (sp-022 exception) |
| Engagement ratio | 1.08 | 1.00 | 1.00 | = |
| Holdover OPS | 0 | 0 | 0 | ✅ 4ᵉ consécutif |
| Commits `--no-verify` | 0 | 0 | 0 | ✅ 4ᵉ consécutif |
| Coverage CI | 68 % | 70 % | ~70 % | = (T-112-05 + T-113-05 Integration tests) |
| PRs mergées | 12 | 8 | 28 | ↗️ exec accélérée |
| Lines added | ~2200 | ~1800 | ~5500 | ↗️ |

## 🚀 ROI sprint-024 (vs estimation)

| Item | Estimé | Livré | Δ |
|---|---|---|---|
| OPS-PRE5-DECISION | 1 pt (1.5h) | 1 pt (smoke 5 min + ADR-0018 1h + atelier 1h) | ✅ |
| US-110 KPI DSO | 3 pts (11h) | 3 pts (11h) | ✅ exact |
| US-111 KPI lead time | 3 pts (11h) | 3 pts (11h) | ✅ exact |
| US-112 KPI adoption | 2 pts (7h) | 2 pts (7h) | ✅ exact |
| US-113 Migration | 3 pts (12h) | 3 pts (12h) | ✅ exact |
| **Total** | **12 pts (42.5h)** | **12 pts (42.5h)** | ✅ **estimation parfaite** |

## 🔗 Liens

- Sprint review : `sprint-review.md`
- Atelier OPS-PREP-J0 : `atelier-ops-prep-j0.md`
- Sprint goal : `sprint-goal.md`
- Sprint-023 retro : `../sprint-023-epic-003-phase-3-finition/sprint-retro.md`
- ADR-0017 sub-epic B out backlog
- ADR-0018 Render redeploy Option A
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- Runbook WorkItem migration : `../../../docs/runbooks/workitem-cost-migration.md`

---

**Auteur** : Tech Lead
**Date** : 2026-05-13
**Version** : 1.0.0
