# Sprint Retrospective — Sprint 022

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 022 — EPIC-003 Phase 3 Completion |
| Date | 2026-05-11 (clôture anticipée) |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Recalibrage A-1 sprint-021 retro tient** : 12 pts ferme livré 100 %, +1 cap libre = 108 %. Aligné moyenne 13 sprints (~11 pts). Vélocité durable confirmée vs sprint-021 exception 117 %. |
| K-2 | **Runbook OPS-PREP-J0 §3 décision matrix appliquée strictement** : ADR-0017 Sub-epic B Out Backlog acté = 2ᵉ sprint consécutif 0 holdover OPS. Pattern crédible vs holdover silent. |
| K-3 | **TDD strict Domain pure** : US-104 Project margin snapshot (7 tests Domain) + US-105 dual dispatch (2 tests régression) + WORKFLOW-YAML (6 tests Integration). Source of truth Domain confirmée. |
| K-4 | **Strangler fig pattern 2 fois sprint-022** : (1) AlertDetectionService dual dispatch coexistence Domain Event + legacy ; (2) Symfony Workflow YAML coexiste Domain state machine. Maturité pattern 8 applications sprint-021+022. |
| K-5 | **Pivot pragmatic TEST-COVERAGE-011** : Notification + Settings BCs flat only détectés sprint J0 → pivot Vacation + Contributor Events/Exceptions (20 tests ROI optimal). Adaptabilité scope. |
| K-6 | **AT-3 = B Out Backlog documenté ADR-0017** : traçabilité décision structurelle + critères replan futur explicites (owner + 4 credentials simultanés). Pattern reproductible sprints futurs. |
| K-7 | **BUFFER Integration tests 50 % livré** : 2/4 composants déférés sprint-021 rattrapés (DoctrineEmploymentPeriodAdapter + AUDIT-DAILY-HOURS). Reste 2 (WeeklyTimesheetController + Workflow E2E) sprint-023+. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **2 BUFFER Integration tests reportés sprint-023+** (WeeklyTimesheetController Functional + Workflow E2E cross-aggregate). Composants HTTP/E2E lourd vs DB-only. | Sprint-023 BUFFER suite (1-2 pts) OU sprint-024+ dédié E2E avec fixtures auth. |
| L-2 | **Dual dispatch sprint-022 US-105** = transition pas elimination. `LowMarginAlertEvent` legacy toujours dispatched (préserve in-app notifications via NotificationSubscriber). Removal sprint-023+ après refactor NotificationSubscriber Domain Events directement. | Sprint-023 sub-epic refactor NotificationSubscriber (1-2 pts estimation). |
| L-3 | **Margin snapshot transient US-104** : pas persisté Doctrine. Recalcul à chaque consultation Project. Acceptable sprint-022 MVP, mais cost compute scale-up. | Sprint-023+ migration Doctrine cols margin si demande PO (estimation 2 pts). |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Mesurer coverage réel post sprint-022** (CI report ou local PHPUnit --coverage). Sprint-021 retro A-2 héritage indique 65 % estimated — vérifier 68 % cible step 11 atteinte. | Métrique factuelle vs estimation. Informe scope TEST-COVERAGE-012 sprint-023+. |
| S-2 | **Sprint dédié OPS replan timeline** : si owner aligné + 4 credentials confirmés J0 atelier sprint-N → exécuter Sub-epic B holdover. ADR-0017 trigger réversibilité documenté. | Évite Sub-epic B dormant indéfiniment. Re-évaluation sprint-024 ou sprint-025 (1 sprint sur 3-4). |
| S-3 | **Audit `/team:audit` recommendations top 5 cleanup batch** : Mago lint 627 errors (sprint-021 audit), Rector batch 100 % appliqué sprint-022 ✅. Reste Mago = sprint-024+ dédié. | Réduit dette code legacy + améliore /team:audit score (75 → 85+). |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Engagement ferme > 12 pts sans circumstances exceptionnelles documentées** (héritage sprint-021 retro ST-1). Sprint-022 livré 13 pts (12 ferme + 1 libre) = pattern viable. Pas dépasser 12 ferme. | Vélocité 11-13 pts moyenne durable. Risk holdover si > 13. |
| ST-2 | **Tests Integration HTTP/E2E déférés sans timeline clair** (sprint-021 4 composants → 2 livrés sprint-022 + 2 reportés). Sans calendrier, dette grossit. | Sprint-023 BUFFER suite OBLIGATOIRE OR re-évaluation scope (drop si pas valeur). |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **ADR pour décisions structurelles Out Backlog / réversibilité** (ADR-0017 pattern) — capture critères + triggers reproductibles. Étendre sprints futurs si autres décisions structurelles émergent. | Traçabilité décisions + crédibilité runbook patterns. |
| M-2 | **Tests Integration Domain pur avec ContributorFactory + ResetDatabase** (sprint-022 BUFFER) — pattern simple + rapide. Réutiliser sprint-023+ pour rattrapage composants restants. | ROI tests vs effort fixtures. |
| M-3 | **Composer require + recipe + custom config + Integration tests** (sprint-022 WORKFLOW-YAML pattern) — install package safe avec coverage tests. Réutilisable sprint-023+ si autres packages à intégrer. | Process install propre + validation. |

---

## 🎯 Actions concrètes Sprint 023

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Atelier OPS-PREP-J0 J-2 sprint-023 (runbook §2 — 6 questions × stories candidates) | PO + Tech Lead | Sprint-023 J-2 |
| A-2 | Mesurer coverage actuel post sprint-022 (CI report ou local --coverage) | Tech Lead | Sprint-023 J-1 |
| A-3 | Sprint-023 scope PO décision (configurabilité Q5.1 D / refactor NotificationSubscriber / persistence margin / BUFFER Integration suite / Mago lint) | PO | Sprint-023 Planning P1 |
| A-4 | Métrique « 0 holdover OPS » suivie sprint-023 retro (cible 3ᵉ sprint consécutif) | Tech Lead | Sprint-023 retro |
| A-5 | Maintenir baseline 12 pts ferme (recalibrage durable acté sprint-022) | PO + Tech Lead | Sprint-023 Planning P1 |
| A-6 | Évaluer Mago lint cleanup batch (audit top 5 #3) sprint-024+ dédié OR drop selon ROI | Tech Lead | Sprint-023 retro |

---

## 📊 Directive Fondamentale

> « Indépendamment de ce que nous découvrons aujourd'hui, nous comprenons et
> croyons sincèrement que chacun a fait du mieux qu'il pouvait, étant donné
> ce qui était connu à ce moment-là, ses compétences et capacités, les
> ressources disponibles et la situation rencontrée. »

---

## 🚀 Sprint-022 takeaway

**EPIC-003 Phase 3 livré complet sprint-021 + 022** : saisie WorkItem +
Workflow 4 états + UI grille hebdo + UC CalculateProjectMargin + alerte
Slack marge + audit data quality. Phase 3 production ready.

**Recalibrage vélocité 12 pts ferme tient** : sprint-022 livre 13 pts
(12 ferme + 1 libre) = 108 % aligné moyenne 13 sprints ~11 pts. Sprint-021
17 pts = exception confirmée.

**Pattern strangler fig 8 applications cumulées sprint-021+022** :
maturité pattern confirmée. Co-existence Domain + legacy systématique
sans break.

**ADR-0017 décision structurelle** : Sub-epic B Out Backlog 4ᵉ holdover
runbook §3 strict. Pattern reproductible sprints futurs.

**Indicateur santé équipe** : 2ᵉ sprint consécutif 0 holdover OPS,
TDD Domain pure 30+ tests sprint-022, 12 tests Integration ajoutés
(BUFFER 50 % + WORKFLOW), PHPStan max 0 erreur maintenu, composer audit
0 vulnérabilité.

**Risk visible sprint-023** :
- 2 BUFFER Integration tests reportés (ST-2) — calendrier sprint-023
  OBLIGATOIRE OR scope drop
- LowMarginAlertEvent legacy toujours dispatched (L-2) — refactor
  NotificationSubscriber sprint-023+ requis pour removal
- Margin snapshot transient (L-3) — recalcul à chaque consultation
  Project si scale-up

---

## 🔗 Liens

- Sprint-022 review : `sprint-review.md`
- Sprint-021 retro : `../sprint-021-epic-003-phase-3/sprint-retro.md`
- Sprint-023 kickoff : `../sprint-023-epic-003-phase-3-finition/sprint-goal.md`
- ADR-0017 OPS Sub-epic B Out Backlog
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
