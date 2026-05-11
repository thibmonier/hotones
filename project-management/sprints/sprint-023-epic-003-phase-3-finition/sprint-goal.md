# Sprint 023 — EPIC-003 Phase 3 Finition + Coverage Step 12

| Champ | Valeur |
|---|---|
| Numéro | 023 |
| Début | 2026-05-12 (kickoff) |
| Fin | 2026-05-26 (clôture cible) |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme + 1-2 pts libre** (recalibrage durable confirmé sprint-022) |
| Engagement ferme | **12 pts** |
| Statut backlog | **Provisoire** — Sprint Planning P1 (sprint-022 retro actions A-3 + A-5) |

---

## 🎯 Sprint Goal

> « EPIC-003 Phase 3 finition : refactor `NotificationSubscriber` Domain
> Events directement + suppression `LowMarginAlertEvent` legacy
> (sprint-022 US-105 strangler fig completion). BUFFER tests Integration
> sprint-021 suite (WeeklyTimesheetController Functional + Workflow E2E).
> Coverage step 12 (68 → 70 %). Application stricte runbook OPS-PREP-J0
> J-2 → 0 holdover OPS 3ᵉ sprint consécutif. »

**Recalibrage durable** : engagement ferme 12 pts (acté sprint-022 retro
ST-1). Vélocité moyenne 13 sprints ~11 pts.

---

## ⚠️ Pré-requis J0 obligatoires

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 J-2 sprint-023 (runbook §2) | PO + Tech Lead | Sprint-023 J-2 | ⏳ A-1 héritage |
| PRE-2 | Mesurer coverage actuel post sprint-022 (CI report) | Tech Lead | Sprint-023 J-1 | ⏳ A-2 héritage |
| PRE-3 | Décision PO scope sprint-023 (configurabilité Q5.1 D / refactor NotificationSubscriber / persistence margin / BUFFER Integration / Mago) | PO | Sprint-023 Planning P1 | ⏳ A-3 héritage |
| PRE-4 | Stories sprint-023 spécifiées 3C + Gherkin (US-106..US-109) | PO | Sprint-023 J0 fin | ⏳ |
| PRE-5 | Render prod redeploy + clear cache (image stale 2026-01-12) | Tech Lead user | Hors sprint, tracked manuellement | ⏳ user-tracked 5ᵉ sprint consécutif |

---

## Backlog provisoire — 12 pts ferme

> **Note** : estimations provisoires Sprint Planning P1. Atelier OPS-PREP-J0 J-2 figera scope.

### Sub-epic A — Refactor NotificationSubscriber + LowMarginAlertEvent removal (3 pts)

| ID | Titre | Pts | Source |
|---|---|---:|---|
| US-106 | Refactor `NotificationSubscriber` → consume `MarginThresholdExceededEvent` Domain Event directement (translator OR new handler crée Notification entity). Suppression `LowMarginAlertEvent` legacy + drop dual dispatch `AlertDetectionService` (US-105 sprint-022). Tests régression in-app notifications. | 3 | Sprint-022 retro L-2 + AT-3.3 ADR-0016 |

### Sub-epic B — BUFFER Integration tests sprint-021 suite (2 pts)

| ID | Titre | Pts | Source |
|---|---|---:|---|
| TEST-INTEGRATION-21-SUITE | Tests Integration restants sprint-021 : `WeeklyTimesheetController` Functional (Panther + auth + DB fixtures) + Workflow E2E cross-aggregate Invoice listener trigger validation. **OBLIGATOIRE sprint-023** (sprint-022 retro ST-2 trigger). | 2 | Sprint-021 retro A-5 + sprint-022 retro ST-2 |

### Sub-epic C — Coverage step 12 (2 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| TEST-COVERAGE-012 | Push coverage 68 → 70 % via Domain Order/Invoice edge cases additionnels (Aggregate Root extensions ou Event/Exception manquants — pattern sprint-022 TEST-COVERAGE-011 pivot pragmatic) | 2 | Sprint-022 retro héritage + audit recommendation #4 |

### Sub-epic D — Persistence margin snapshot Project (3 pts — selon PO décision)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-107 | Migration Doctrine `projects` table cols `cout_total_cents` + `facture_total_cents` + `marge_calculated_at` + persistence Project margin snapshot (US-104 sprint-022 transient). Tests Integration migration up/down. | 3 | Sprint-022 retro L-3 — selon demande PO scale-up |

### Sub-epic E — Configurabilité hiérarchique seuil marge (2 pts — selon PO décision Q5.1 D)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-108 | Configurabilité hiérarchique seuil marge ADR-0016 Q5.1 D : default global → override par Client → override par Project. Migration Doctrine cols `margin_threshold_percent` sur Client + Project + UC `CalculateProjectMargin` (US-104) résolution hiérarchique. | 2 | ADR-0016 A-9 |

---

## Capacité libre (1-2 pts) — pré-allocation explicite

Story candidate (sprint-022 retro M-2 + S-3) :
- 4ᵉ Integration test sprint-021 si BUFFER suite reste cap libre
- OU Mago lint cleanup batch initial (audit top 5 #3 — 100 errors / 627)
- OU TEST-COVERAGE-013 step 13 anticipation

Décision Sprint Planning P1 + atelier OPS-PREP-J0 J-2.

---

## Definition of Done

- ✅ Tests Unit + Integration passent (Domain pure host PHP + Integration Docker DB)
- ✅ PHPStan max 0 erreur (CI Docker)
- ✅ CS-Fixer + Rector + Deptrac + Mago OK
- ✅ Snyk Security clean
- ✅ Smoke test post-deploy green sur Render (**après** PRE-5 redeploy clear cache)
- ✅ Documentation à jour (runbook + ADR si nouvelle décision)
- ✅ PR review validée + merge linéaire main
- ✅ **0 commit `--no-verify`** sprint-023
- ✅ **0 holdover OPS sub-epic** sprint-023 (cible runbook OPS-PREP-J0 — 3ᵉ sprint consécutif)
- ✅ BUFFER Integration tests sprint-021 100 % livré (2 composants restants)
- ✅ `LowMarginAlertEvent` legacy supprimé (refactor `NotificationSubscriber` Sub-epic A)
- ✅ Engagement ferme respecté (12 pts max — recalibrage durable acté sprint-022)

---

## 🔗 Cérémonies

| Cérémonie | Date prévue | Statut |
|---|---|---|
| **Atelier OPS-PREP-J0 J-2** | 2026-05-11 ~30 min (runbook §2) | ⏳ |
| Sprint Planning P1 (PO scope figé) | 2026-05-12 09:00 | ⏳ |
| Sprint Planning P2 (équipe technique tasks décomposées) | 2026-05-12 14:00 | ⏳ |
| Daily standup | Quotidien 09:30 | ⏳ |
| Sprint Review | 2026-05-26 14:00 | ⏳ |
| Rétrospective | 2026-05-26 16:30 | ⏳ |

---

## 🎯 Actions héritées sprint-022 retro

| ID | Action | Statut sprint-023 |
|---|---|---|
| A-1 | Atelier OPS-PREP-J0 J-2 sprint-023 | ⏳ PRE-1 |
| A-2 | Mesurer coverage actuel post sprint-022 | ⏳ PRE-2 |
| A-3 | Sprint-023 scope PO décision | ⏳ PRE-3 |
| A-4 | Métrique « 0 holdover OPS » suivie | ⏳ retro fin sprint |
| A-5 | Maintenir baseline 12 pts ferme | ✅ acté kickoff (engagement = 12 pts) |
| A-6 | Évaluer Mago lint cleanup batch sprint-024+ dédié | ⏳ sprint-023 retro |

---

## ⚠️ Issues prod connues hors sprint (tracked manuellement user)

| Issue | Impact | Action |
|---|---|---|
| **Render image stale 2026-01-12** : `/health` sert `<?php` raw octet-stream. Smoke 30+ runs FAIL chronique (5 sprints consécutifs). | Smoke red ≠ régression code. App fonctionne. | Render dashboard manual deploy + clear build cache. User suit déploiement. **Risk croissant** : 5ᵉ sprint consécutif sans redeploy = signal d'arrêt à évaluer. |
| **Sub-epic B OPS holdover** Out Backlog (ADR-0017 sprint-022) | Slack alerts + Sentry alert rules + SMOKE config non actifs prod | Replan sprint dédié OPS quand owner aligné + 4 credentials simultanés confirmés J0 |

---

## ⚠️ Risk visible sprint-023

- **Refactor NotificationSubscriber Sub-epic A** : risk régression in-app
  notifications si translator incomplet. Tests régression critiques.
- **BUFFER Integration tests HTTP/E2E** : fixtures Panther + auth +
  Stimulus asset compilation lourds. Risk +1-2 pts vs estimation.
- **Recalibrage 12 pts ferme** continue à tenir — sprint-023 = 3ᵉ
  validation pattern post-sprint-021 exception.

---

## 📊 Indicateurs cibles fin sprint

- ✅ `NotificationSubscriber` refactor Domain Events directement
- ✅ `LowMarginAlertEvent` legacy supprimé du codebase
- ✅ Coverage 70 % atteint (step 12)
- ✅ BUFFER Integration tests sprint-021 100 % livré (4/4 composants)
- ✅ 0 holdover OPS sub-epic (3ᵉ sprint consécutif)
- ✅ 0 commit `--no-verify`
- ⚠️ Smoke test prod **vert** (post Render redeploy PRE-5 — 5ᵉ sprint
  consécutif user-tracked)

---

## 🔗 Liens

- Sprint-022 review : `../sprint-022-epic-003-phase-3-completion/sprint-review.md`
- Sprint-022 retro : `../sprint-022-epic-003-phase-3-completion/sprint-retro.md`
- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- ADR-0016 — EPIC-003 Phase 3 décisions
- ADR-0017 — OPS Sub-epic B Out Backlog
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
