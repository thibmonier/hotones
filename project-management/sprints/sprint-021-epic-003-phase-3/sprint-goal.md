# Sprint 021 — EPIC-003 Phase 3 RecordWorkItem + invariant journalier

| Champ | Valeur |
|---|---|
| Numéro | 021 |
| Début | 2026-05-12 (kickoff) |
| Fin | 2026-05-26 (clôture cible) |
| Durée | 10 jours ouvrés |
| Capacité | 12 pts (recalibrage continu sprint-019/020 ~92 %) |
| Engagement ferme | **10 pts** + 2 pts capacité libre |
| Statut backlog | **Provisoire** — atelier PO Phase 3 J0 figera scope final |

---

## 🎯 Sprint Goal

> « EPIC-003 Phase 3 : UC `RecordWorkItem` avec invariant journalier
> (`dailyTotal <= dailyMaxHours`), Domain Service `DailyHoursValidator`,
> Workflow Symfony state machine `WorkItem` MVP 2 états + UI saisie minimum.
> Application stricte runbook OPS-PREP-J0 J-2 → 0 holdover OPS sub-epic B
> sprint-021. »

**Atelier PO Phase 3 J0** : 19 questions structurées atelier prep
(`../sprint-020-epic-003-phase-2-acl/atelier-po-phase-3-prep.md`) → ADR-0016
livré J0 + backlog ferme figé Sprint Planning P2.

---

## ⚠️ Pré-requis J0 obligatoires

| ID | Action | Owner | Deadline |
|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 J-2 (runbook §2 — 6 questions screening sur backlog candidat) | PO + Tech Lead | 2026-05-10 |
| PRE-2 | Atelier PO Phase 3 décisions (atelier-po-phase-3-prep.md — 19 questions) | PO + Tech Lead | 2026-05-12 J0 matin |
| PRE-3 | ADR-0016 publié (livrable atelier Phase 3) | Tech Lead | 2026-05-12 J0 fin |
| PRE-4 | Stories US-099..US-102 spécifiées 3C + Gherkin | PO | 2026-05-12 J0 fin |
| PRE-5 | Render prod redeploy + clear cache (image stale 2026-01-12 servant /health raw PHP) | Tech Lead user | Hors sprint, tracked manuellement |

---

## Backlog provisoire (10 pts engagement ferme)

> **Note** : estimations provisoires. Atelier J0 figera scope + estimations.

### Sub-epic A — EPIC-003 Phase 3 RecordWorkItem (8 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-099 | UC `RecordWorkItem` avec invariant journalier (ADR-0015 A-3) | 5 | Inclut `WorkItemRepositoryInterface::findByContributorAndDate` |
| US-100 | Domain Service `DailyHoursValidator` + `DailyHoursExceededException` (ADR-0015 A-4 + A-5) | 2 | Lecture `EmploymentPeriodRepository` (interface Domain) |
| US-101 | Workflow Symfony state machine `WorkItem` MVP 2 états (draft → validated) | 1 | Atelier Q3.1 = B (sprint-021 MVP, billed/paid sprint-022+) |

### Sub-epic B — UI saisie hebdo (2 pts — selon atelier Q1.1)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-102 | Twig formulaire ligne-par-ligne saisie WorkItem MVP | 2 | Reco Tech Lead Q1.1 = B (formulaire vs grille hebdo coûteuse) |

**Si atelier Q1.1 = A (grille hebdo) → réallocation 5 pts capacité libre, drop autre sub-epic.**

### Sub-epic C — Audit data quality (1 pt — selon atelier Q5.3)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| AUDIT-DAILY-HOURS | Étendre `app:audit:contributors-cjm --audit-daily-hours` (EmploymentPeriod weeklyHours/workTimePercentage NULL/aberrant) | 1 | Reco Tech Lead Q5.3 = A (avant deploy Phase 3, pattern AUDIT-CONTRIBUTORS-CJM succès sprint-020) |

### Sub-epic D — OPS holdover décision finale (selon atelier Q6.3)

| ID | Titre | Pts | Notes |
|---|---:|---:|---|
| OPS-DECISION-B | Sub-epic B sprint-019/020 OPS holdover : owner J0 fixé OR Out backlog OR réallocation | 0 (rituel atelier) | Décision atelier Q6.3 — pas de pts si Out backlog |

---

## Capacité libre (2 pts)

À allouer J3-J5 selon avancement ferme :

| Story candidate | Pts | Justification |
|---|---:|---|
| EPIC-003 Phase 3+ démarrage : `MarginThresholdExceededEvent` + alerte Slack | 2 | Anticipation sprint-022 |
| TEST-COVERAGE-011 Domain Notification + Settings BCs | 2 | Coverage 65 → 68 % step 11 |
| BUFFER : extensions tests Domain Workflow state machine | 1-2 | Si US-101 livré tôt |

**Pré-allocation Sprint Planning P1** (sprint-020 retro A-5 — pas de « OR »
vague). Décision PO atelier Q6.4.

---

## Definition of Done

- ✅ Tests Unit + Integration passent (Domain pure host PHP + Integration Docker DB)
- ✅ PHPStan max 0 erreur (CI Docker + local APCu si OrbStack OK)
- ✅ CS-Fixer + Rector + Deptrac + Mago OK
- ✅ Snyk Security clean
- ✅ Smoke test post-deploy green sur Render (**après** PRE-5 redeploy clear cache)
- ✅ Documentation à jour (runbook + ADR si nouvelle décision)
- ✅ PR review validée + merge linéaire main
- ✅ **0 commit `--no-verify`** sprint-021 (sprint-019/020 héritage OrbStack + APCu)
- ✅ **0 holdover OPS sub-epic** sprint-021 (runbook OPS-PREP-J0 application stricte — métrique cible)

---

## 🔗 Cérémonies

| Cérémonie | Date prévue |
|---|---|
| **Atelier OPS-PREP-J0 J-2** | 2026-05-10 ~30 min (runbook §2) |
| **Atelier PO Phase 3 J0** | 2026-05-12 09:00 ~90 min (atelier prep 6 blocs) |
| Sprint Planning P1 (PO scope figé post-atelier) | 2026-05-12 11:00 |
| Sprint Planning P2 (équipe technique tasks décomposées) | 2026-05-12 14:00 |
| Daily standup | Quotidien 09:30 |
| Sprint Review | 2026-05-26 14:00 |
| Rétrospective | 2026-05-26 16:30 |

---

## 🎯 Actions héritées sprint-020 retro

| ID | Action | Owner | Statut sprint-021 |
|---|---|---|---|
| A-1 | Pré-merge livrables sprint-020 (runbook + coverage-010 + atelier prep) | Tech Lead | ✅ fait — PR #208 + #209 + #210 mergées |
| A-2 | Atelier OPS-PREP-J0 J-2 sprint-021 | PO + Tech Lead | ⏳ PRE-1 ce sprint |
| A-3 | Atelier PO Phase 3 EPIC-003 décisions UC `RecordWorkItem` | PO + Tech Lead | ⏳ PRE-2 ce sprint |
| A-4 | Décision finale Sub-epic B OPS holdover | PO + Tech Lead | ⏳ atelier Q6.3 |
| A-5 | Capacité libre = pré-allocation explicite (pas « OR ») | PO | ⏳ atelier Q6.4 |
| A-6 | Métrique « 0 holdover OPS » suivie sprint-021 retro | Tech Lead | ⏳ retro fin sprint |

---

## ⚠️ Issues prod connues hors sprint (tracked manuellement user)

| Issue | Impact | Action |
|---|---|---|
| **Render image stale 2026-01-12** : `/health` sert `<?php` raw octet-stream (42 bytes), nginx static file résiduel sprint-014. 25+ smoke runs FAIL chronique. | Smoke test red ≠ régression code. App fonctionne, Render `healthCheckPath: /health` voit 200. | Render dashboard manual deploy + clear build cache. Tracked user après suivi déploiement. |
| **Sub-epic B OPS holdover sprint-017→020** : Slack webhook + Sentry alerts + SMOKE config. | Pas d'alerting prod actif. | Atelier Q6.3 décision finale (Out OR owner J0 fixé OR réallocation). |

---

## 📊 Indicateurs cibles fin sprint

- UC `RecordWorkItem` opérationnel — saisie WorkItem avec invariant journalier validé
- Workflow Symfony state machine `WorkItem` 2 états MVP intégré
- Coverage 65 % stable (cible step 11 si capacité libre allouée TEST-COVERAGE-011)
- ADR-0016 livré (décisions atelier PO Phase 3)
- 0 commit `--no-verify` sprint-021
- **0 holdover OPS sub-epic** (métrique succès runbook OPS-PREP-J0)
- Smoke test prod **vert** (post Render redeploy PRE-5)

---

## 🔗 Liens

- Sprint-020 review : `../sprint-020-epic-003-phase-2-acl/sprint-review.md`
- Sprint-020 retro : `../sprint-020-epic-003-phase-2-acl/sprint-retro.md`
- Atelier PO Phase 3 prep : `../sprint-020-epic-003-phase-2-acl/atelier-po-phase-3-prep.md`
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- ADR-0013 EPIC-003 scope WorkItem & Profitability
- ADR-0015 EPIC-003 Phase 2 décisions task=NULL + doublons + invariant journalier
- Audit Contributors CJM : `../../../docs/02-architecture/epic-003-audit-contributors-cjm-runbook.md`
- Audit data EPIC-003 : `../../../docs/02-architecture/epic-003-audit-existing-data.md`
