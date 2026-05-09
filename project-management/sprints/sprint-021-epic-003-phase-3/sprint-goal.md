# Sprint 021 — EPIC-003 Phase 3 RecordWorkItem + Workflow + UI grille hebdo

| Champ | Valeur |
|---|---|
| Numéro | 021 |
| Début | 2026-05-12 (kickoff) |
| Fin | 2026-05-26 (clôture cible) |
| Durée | 10 jours ouvrés |
| Capacité | **17 pts ferme + 2-3 pts libre = 19-20 pts** (challenge vélocité +70 % vs moyenne 10) |
| Engagement ferme | **17 pts** |
| Statut backlog | **✅ Ferme** — décisions atelier PO Phase 3 actées ADR-0016 |

---

## 🎯 Sprint Goal

> « EPIC-003 Phase 3 livraison complète : UC `RecordWorkItem` avec invariant
> journalier flexible (warning override), Domain Service `DailyHoursValidator`,
> Workflow Symfony 4 états (`draft → validated → billed → paid`) avec
> validation role-based managers, UI Twig grille hebdo saisie + auto-save.
> Capacité libre `MarginThresholdExceededEvent` + alerte Slack 10 % marge.
> Application stricte runbook OPS-PREP-J0 J-2 → 0 holdover OPS sub-epic B
> sprint-021. »

**Décisions atelier** : ADR-0016 livré (`docs/02-architecture/adr/0016-epic-003-phase-3-decisions.md`).

---

## ⚠️ Pré-requis J0 obligatoires

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 J-2 (runbook §2 — 6 questions screening sur 5 stories candidate) | PO + Tech Lead | 2026-05-10 | ⏳ |
| PRE-2 | Atelier PO Phase 3 décisions (atelier-po-phase-3-prep.md) | PO + Tech Lead | 2026-05-09 ✅ | ✅ acté ADR-0016 |
| PRE-3 | ADR-0016 publié | Tech Lead | 2026-05-09 ✅ | ✅ |
| PRE-4 | Stories US-099..US-102 spécifiées 3C + Gherkin selon ADR-0016 | PO | 2026-05-12 J0 fin | ⏳ |
| PRE-5 | Render prod redeploy + clear cache (image stale 2026-01-12) | Tech Lead user | Hors sprint, tracked manuellement | ⏳ user-tracked |
| PRE-6 | Validation 4 OQ ouvertes ADR-0016 (managers role / WorkItem manager / Q5.1 timing / Q6.4 scope) | PO | 2026-05-12 Sprint Planning P2 | ⏳ |

---

## Backlog ferme — 17 pts

### Sub-epic A — UC RecordWorkItem + Workflow (11 pts)

| ID | Titre | Pts | ADR-0016 |
|---|---|---:|---|
| US-099 | UC `RecordWorkItem` (champs Q1.2 / step Q1.3 / auto-save Q2.1 / édition Q2.2 / override admin Q2.3 / warning Q2.4 / role-based Q3.2) | **5** | A-3 |
| US-100 | Domain Service `DailyHoursValidator` + `DailyHoursWarningException` | **2** | A-4 |
| US-101 | Workflow Symfony state machine `work_item` 4 états + cross-aggregate Invoice listeners | **4** | A-1 + A-2 + A-10 |

### Sub-epic B — UI grille hebdo (5 pts)

| ID | Titre | Pts | ADR-0016 |
|---|---|---:|---|
| US-102 | Twig grille hebdo `/timesheet/{week}` (jours × projets) + drag-drop + auto-save + warning UI Q2.4 + édition lock Q2.2 | **5** | A-5 |

### Sub-epic C — Audit data quality (1 pt)

| ID | Titre | Pts | ADR-0016 |
|---|---|---:|---|
| AUDIT-DAILY-HOURS | Étendre `app:audit:contributors-cjm --audit-daily-hours` (EmploymentPeriod weeklyHours/workTimePercentage NULL/aberrant) | **1** | A-6 |

### Sub-epic D — OPS holdover décision finale (rituel)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| OPS-DECISION-B | Sub-epic B sprint-019/020 OPS holdover : décision atelier OPS-PREP-J0 J-2 (Q6.3 = A si owner J0 confirmé sinon B Out backlog) | 0-0.5 | Si A retenu → +0.5 pt sub-epic B (Slack webhook + Sentry alerts + SMOKE) |

---

## Capacité libre (2-3 pts) — pré-allouée Q6.4

| Story | Pts | ADR-0016 |
|---|---:|---|
| US-103 `MarginThresholdExceededEvent` Domain Event + handler async Slack alerting (`#alerts-prod` via SlackAlertingService US-094, seuil 10 % défaut Q5.2) | **2-3** | A-7 |

⚠️ **Note Q6.4 + OQ-4** : sprint-021 = event + alerting (sans UC `CalculateProjectMargin`
complet — ce dernier sprint-022 A-8). Validation PO Sprint Planning P2.

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
- ✅ Audit log loggé pour overrides admin (Q2.3) + override seuil journalier user (Q2.4)
- ✅ Workflow Symfony state machine validée par tests Integration (transitions valides + invalides)

---

## 🔗 Cérémonies

| Cérémonie | Date prévue | Statut |
|---|---|---|
| **Atelier OPS-PREP-J0 J-2** | 2026-05-10 ~30 min (runbook §2) | ⏳ |
| Atelier PO Phase 3 J0 | 2026-05-09 (anticipé) ~90 min | ✅ acté |
| Sprint Planning P1 (PO scope figé post-atelier) | 2026-05-12 09:00 | ⏳ |
| Sprint Planning P2 (équipe technique tasks décomposées + valider OQ-1..OQ-4) | 2026-05-12 14:00 | ⏳ |
| Daily standup | Quotidien 09:30 | ⏳ |
| Sprint Review | 2026-05-26 14:00 | ⏳ |
| Rétrospective | 2026-05-26 16:30 | ⏳ |

---

## 🎯 Actions héritées sprint-020 retro

| ID | Action | Statut sprint-021 |
|---|---|---|
| A-1 | Pré-merge livrables sprint-020 | ✅ fait — PR #208 + #209 + #210 mergées |
| A-2 | Atelier OPS-PREP-J0 J-2 sprint-021 | ⏳ PRE-1 (2026-05-10) |
| A-3 | Atelier PO Phase 3 EPIC-003 décisions | ✅ acté ADR-0016 (2026-05-09) |
| A-4 | Décision finale Sub-epic B OPS holdover | ⏳ atelier OPS-PREP-J0 J-2 (Q6.3) |
| A-5 | Capacité libre = pré-allocation explicite | ✅ Q6.4 = US-103 MarginThresholdExceededEvent + alerte (2-3 pts) |
| A-6 | Métrique « 0 holdover OPS » suivie sprint-021 retro | ⏳ retro fin sprint |

---

## ⚠️ Issues prod connues hors sprint (tracked manuellement user)

| Issue | Impact | Action |
|---|---|---|
| **Render image stale 2026-01-12** : `/health` sert `<?php` raw octet-stream (42 bytes), nginx static file résiduel sprint-014. Smoke 25+ runs FAIL chronique. | Smoke red ≠ régression code. App fonctionne, Render `healthCheckPath: /health` voit 200. | Render dashboard manual deploy + clear build cache. User suit déploiement. |

---

## ⚠️ Questions ouvertes ADR-0016 (à valider Sprint Planning P2)

| OQ | Question | Default TL |
|---|---|---|
| OQ-1 | Q3.2 interprétation : managers self-validate via ROLE_MANAGER + ROLE_ADMIN ? | ✅ Oui |
| OQ-2 | Q3.2 « manager d'un WorkItem » : manager direct contributor / tous managers / manager projet ? | Manager direct contributor |
| OQ-3 | Q5.1 timing : alerte marge configurable sprint-022 (default) OU sprint-021 ? | Sprint-022 |
| OQ-4 | Q6.4 capacité libre : event + alerting sprint-021 OU UC `CalculateProjectMargin` complet sprint-021 ? | Event + alerting sprint-021 (UC complet sprint-022) |

---

## 📊 Indicateurs cibles fin sprint

- ✅ UC `RecordWorkItem` opérationnel — saisie WorkItem avec invariant journalier flexible (warning override Q2.4)
- ✅ Workflow Symfony state machine `work_item` 4 états livré + cross-aggregate Invoice listeners
- ✅ UI Twig grille hebdo saisie + auto-save fonctionnelle
- ✅ Coverage 65 % stable minimum (cible step 11 si capacité absorbée)
- ✅ ADR-0016 livré (✅ acté 2026-05-09)
- ✅ 0 commit `--no-verify` sprint-021
- ✅ **0 holdover OPS sub-epic** (métrique succès runbook OPS-PREP-J0)
- ✅ `MarginThresholdExceededEvent` + alerte Slack opérationnel (capacité libre)
- ✅ Audit `--audit-daily-hours` exécuté + corrections admin appliquées avant deploy Phase 3
- ⚠️ Smoke test prod **vert** (post Render redeploy PRE-5)
- ⚠️ Risk holdover sprint-022 si vélocité 17 pts pas tenue (recalibrage retro)

---

## 🔗 Liens

- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- ADR-0015 — EPIC-003 Phase 2 décisions task=NULL + doublons + invariant journalier
- **ADR-0016** — EPIC-003 Phase 3 décisions UC RecordWorkItem + Workflow + UI saisie
- Atelier prep PO Phase 3 : `../sprint-020-epic-003-phase-2-acl/atelier-po-phase-3-prep.md`
- Sprint-020 review : `../sprint-020-epic-003-phase-2-acl/sprint-review.md`
- Sprint-020 retro : `../sprint-020-epic-003-phase-2-acl/sprint-retro.md`
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- Audit Contributors CJM : `../../../docs/02-architecture/epic-003-audit-contributors-cjm-runbook.md`
- Audit data EPIC-003 : `../../../docs/02-architecture/epic-003-audit-existing-data.md`
- US-094 SlackAlertingService (réutilisé US-103) : sprint-017
