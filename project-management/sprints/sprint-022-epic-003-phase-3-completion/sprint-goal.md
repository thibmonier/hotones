# Sprint 022 — EPIC-003 Phase 3 Completion + Coverage Step 11

| Champ | Valeur |
|---|---|
| Numéro | 022 |
| Début | 2026-05-12 (kickoff) |
| Fin | 2026-05-26 (clôture cible) |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme + 1-2 pts libre** (recalibrage trigger réversibilité ADR-0016 Q6.1 sprint-021 retro A-1) |
| Engagement ferme | **12 pts** |
| Statut backlog | **Provisoire** — Sprint Planning P1 (sprint-021 retro action A-7 + atelier OPS-PREP-J0 J-2) |

---

## 🎯 Sprint Goal

> « EPIC-003 Phase 3 completion : UC `CalculateProjectMargin` complet
> (caller dispatch `MarginThresholdExceededEvent` US-103 livré) + refactor
> `AlertDetectionService` legacy → dispatch nouveau Domain Event +
> `LowMarginAlertEvent` `@deprecated`. Coverage step 11 (65 → 68 %) via
> Domain Notification + Settings BCs. BUFFER tests Integration sprint-021
> rattrapage. Application stricte runbook OPS-PREP-J0 J-2 → 0 holdover OPS
> sprint-022. »

**Recalibrage explicite** : engagement ferme 12 pts (vs 17 sprint-021
exception). Sprint-021 retro action A-1 actée.

---

## ⚠️ Pré-requis J0 obligatoires

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 J-2 sprint-022 (runbook §2 — 6 questions × stories candidates) | PO + Tech Lead | 2026-05-10 (en parallèle sprint-021 closure) | ⏳ |
| PRE-2 | Mesurer coverage actuel post sprint-021 (CI report ou local PHPUnit --coverage) | Tech Lead | Sprint-022 J-1 | ⏳ A-2 héritage |
| PRE-3 | Décision finale OPS-DECISION-B sub-epic B holdover (A go +0.5 pt / B Out backlog) | PO + Tech Lead | Sprint-022 J0 atelier | ⏳ A-4 héritage |
| PRE-4 | Stories sprint-022 spécifiées 3C + Gherkin (US-104..US-107) | PO | Sprint-022 J0 fin | ⏳ |
| PRE-5 | Render prod redeploy + clear cache (image stale 2026-01-12) | Tech Lead user | Hors sprint, tracked manuellement | ⏳ user-tracked sprint-019→021 |

---

## Backlog provisoire — 12 pts ferme

> **Note** : estimations provisoires Sprint Planning P1. Atelier J0 + OPS-PREP-J0 J-2 figeront scope.

### Sub-epic A — UC `CalculateProjectMargin` complet (5 pts)

| ID | Titre | Pts | ADR-0016 |
|---|---|---:|---|
| US-104 | UC `CalculateProjectMargin` — `Project::getMargeAbsolute/Percent/CoutTotal/FactureTotal/getMargeCalculatedAt` (5 méthodes ADR-0016 Q4.3) + handler async `RecalculateProjectMarginOnWorkItemRecorded` consume `WorkItemRecordedEvent` (sprint-019 livré) → calcule marge → dispatch `MarginThresholdExceededEvent` US-103 si seuil dépassé | 5 | A-8 |

### Sub-epic B — Refactor legacy alerting (2 pts)

| ID | Titre | Pts | ADR-0016 |
|---|---|---:|---|
| US-105 | Refactor `AlertDetectionService` (`src/Service/AlertDetectionService.php` ligne 120) → dispatch `MarginThresholdExceededEvent` Domain Event (US-103) au lieu de legacy `App\Event\LowMarginAlertEvent`. Marquer `LowMarginAlertEvent` `@deprecated` PHPDoc. Tests régression. | 2 | AT-3.3 strangler fig |

### Sub-epic C — Coverage step 11 (2 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| TEST-COVERAGE-011 | Push coverage 65 → 68 % via Domain Notification + Settings BCs Aggregate extensions | 2 | Sprint-020/021 retro héritage step 11 |

### Sub-epic D — Sub-epic B OPS holdover finalisation — **OUT BACKLOG** (ADR-0017)

| ID | Titre | Pts | Notes |
|---|---:|---:|---|
| ~~OPS-SUB-EPIC-B~~ | **OUT BACKLOG** — décision AT-3 = B (atelier OPS-PREP-J0 J-2 sprint-022). 4ᵉ holdover consécutif = signal arrêt runbook §3. Replan sprint dédié OPS quand owner aligné + 4 credentials simultanés confirmés. Voir ADR-0017. | 0 | 1 pt réalloué capacité libre |

### Sub-epic E — BUFFER tests Integration sprint-021 (1-2 pts)

| ID | Titre | Pts | Notes |
|---|---:|---:|---|
| TEST-INTEGRATION-21 | Tests Integration Docker DB rattrapage sprint-021 : `DoctrineEmploymentPeriodAdapter` + `WorkflowTransition` E2E + `WeeklyTimesheetController` Functional + `AuditDailyHours` script Integration | 1-2 | Sprint-021 retro A-5 + ST-2 héritage |

### Sub-epic F — Symfony Workflow YAML config (capacité libre — selon S-3)

| ID | Titre | Pts | Notes |
|---|---:|---:|---|
| WORKFLOW-YAML | `composer require symfony/workflow` + `config/packages/workflow.yaml` 4 états (sprint-021 livre Domain state machine, intégration Symfony Workflow optionnelle pour UI/listeners) | 1 | Sprint-021 retro S-3 — évaluer ROI selon demande PO (dashboard workflow UI ?) |

---

## Capacité libre (1-2 pts) — pré-allocation explicite (sprint-020 retro A-5 héritage)

Story candidate :
- WORKFLOW-YAML (sub-epic F) si demande PO UI workflow visuel
- OU Rector batch 83 files sprint-021 audit recommendation (PropertyPromotion + modern PHP modernisation safe)
- OU TEST-COVERAGE-012 step 12 (68 → 70 %) anticipation

Décision Sprint Planning P1 + Q6.4 sprint-022 atelier.

---

## Definition of Done

- ✅ Tests Unit + Integration passent (Domain pure host PHP + Integration Docker DB)
- ✅ PHPStan max 0 erreur (CI Docker)
- ✅ CS-Fixer + Rector + Deptrac + Mago OK
- ✅ Snyk Security clean
- ✅ Smoke test post-deploy green sur Render (**après** PRE-5 redeploy clear cache)
- ✅ Documentation à jour (runbook + ADR si nouvelle décision)
- ✅ PR review validée + merge linéaire main
- ✅ **0 commit `--no-verify`** sprint-022
- ✅ **0 holdover OPS sub-epic** sprint-022 (cible runbook OPS-PREP-J0)
- ✅ Tests Integration sprint-021 4 composants livrés (TEST-INTEGRATION-21)
- ✅ Engagement ferme respecté (12 pts max — recalibrage durable)

---

## 🔗 Cérémonies

| Cérémonie | Date prévue | Statut |
|---|---|---|
| **Atelier OPS-PREP-J0 J-2** | 2026-05-10 ~30 min (runbook §2) | ⏳ |
| Sprint Planning P1 (PO scope figé) | 2026-05-12 09:00 | ⏳ |
| Sprint Planning P2 (équipe technique tasks décomposées) | 2026-05-12 14:00 | ⏳ |
| Daily standup | Quotidien 09:30 | ⏳ |
| Sprint Review | 2026-05-26 14:00 | ⏳ |
| Rétrospective | 2026-05-26 16:30 | ⏳ |

---

## 🎯 Actions héritées sprint-021 retro

| ID | Action | Statut sprint-022 |
|---|---|---|
| A-1 | Recalibrer engagement ferme à 12 pts | ✅ acté (engagement ferme = 12 pts) |
| A-2 | Mesurer coverage actuel post sprint-021 | ⏳ PRE-2 |
| A-3 | Atelier OPS-PREP-J0 J-2 sprint-022 | ⏳ PRE-1 |
| A-4 | Décision finale OPS-DECISION-B explicite | ⏳ PRE-3 |
| A-5 | BUFFER tests Integration sprint-021 (4 composants) | ⏳ sub-epic E |
| A-6 | Métrique « 0 holdover OPS » suivie | ⏳ retro fin sprint |
| A-7 | Décision PO scope sprint-022 (UC complet + refactor + coverage + OPS si A) | ⏳ Planning P1 |

---

## ⚠️ Issues prod connues hors sprint (tracked manuellement user)

| Issue | Impact | Action |
|---|---|---|
| **Render image stale 2026-01-12** : `/health` sert `<?php` raw octet-stream. Smoke 25+ runs FAIL chronique. | Smoke red ≠ régression code. App fonctionne. | Render dashboard manual deploy + clear build cache. User suit déploiement. |

---

## ⚠️ Risk visible sprint-022

- Recalibrage vélocité 12 pts → tester durabilité (sprint-021 = 17 pts exception)
- Refactor `AlertDetectionService` US-105 : tests régression critique (legacy event utilisé production)
- BUFFER Integration tests sprint-021 : 4 composants déférés peuvent prendre +1-2 pts vs estimation

---

## 📊 Indicateurs cibles fin sprint

- ✅ UC `CalculateProjectMargin` opérationnel + handler async dispatch event US-103 si seuil
- ✅ Refactor legacy alerting → Domain Event (sub-epic B)
- ✅ Coverage 68 % atteint (step 11)
- ✅ Tests Integration sprint-021 4 composants livrés
- ✅ 0 holdover OPS sub-epic (métrique runbook OPS-PREP-J0 — 2ᵉ sprint consécutif)
- ✅ 0 commit `--no-verify`
- ⚠️ Smoke test prod **vert** (post Render redeploy PRE-5)
- ⚠️ Sub-epic B OPS Slack/Sentry/SMOKE configurés si AT-3 = A

---

## 🔗 Liens

- Sprint-021 review : `../sprint-021-epic-003-phase-3/sprint-review.md`
- Sprint-021 retro : `../sprint-021-epic-003-phase-3/sprint-retro.md`
- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- ADR-0016 — EPIC-003 Phase 3 décisions
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- US-103 MarginThresholdExceededEvent (livré sprint-021) — réutilisé US-104 + US-105
