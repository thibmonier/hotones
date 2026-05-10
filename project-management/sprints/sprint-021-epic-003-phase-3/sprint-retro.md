# Sprint Retrospective — Sprint 021

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 021 — EPIC-003 Phase 3 RecordWorkItem + Workflow + UI |
| Date | 2026-05-10 (clôture anticipée) |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Runbook OPS-PREP-J0 appliqué J-2 sprint-021** (sprint-020 retro A-2 + S-1 héritage) → 0 holdover OPS sub-epic B première occurrence depuis sprint-016. Métrique cible runbook atteinte. Pattern à reproduire systématiquement. |
| K-2 | **Atelier PO Phase 3 J0 (anticipé sprint-020 J fin)** → ADR-0016 19 décisions + 4 OQ tranchées sans bloquer sprint-021 J1. Pattern atelier décisions amont continue ROI. |
| K-3 | **Strangler fig pattern appliqué 6 fois sprint-021** (EmploymentPeriod ACL / WorkItem status reconstitute / InvoiceCreatedEvent payload / MarginThresholdExceededEvent vs legacy / UI /timesheet/week/* / migration default draft) sans casser legacy. Maturité pattern sprint-008-013 confirmée. |
| K-4 | **TDD strict Domain pure host PHP** (sans Docker) sur 50+ tests sprint-021 (US-099/100/101/103). Tests rapides + résilience env CI. |
| K-5 | **Tech Lead pré-vérification AT-3 dépendances** sprint-021 J-2 → trouvé `findByContributorAndDate` + Invoice events + SlackAlertingService déjà livrés sprints précédents. Évite duplication code + estimation surprise mid-sprint. |
| K-6 | **Refactor proactif `SlackAlertingInterface` extraction** sprint-021 US-103 → débloque tests Application Layer + pattern réutilisable sprint-022+. |
| K-7 | **Capacité libre Q6.4 pré-allouée explicite** (sprint-020 retro A-5 héritage) → MarginThresholdExceededEvent livré dans budget +3 pts sans drift scope. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **17 pts ferme = +70 % vélocité moyenne acté Q6.1** : livré 20 pts (117 %) — succès cette fois mais point haut historique. Risk reproductibilité 0 si conditions différentes. | Sprint-022 baseline retour à 12 pts ferme (recalibrage trigger réversibilité ADR-0016 Q6.1). 17 pts = exception sprint-021 driven par décisions PO Q1.1+Q3.1, pas vélocité durable. |
| L-2 | **OPS-DECISION-B sub-epic B holdover** sprint-019/020 — atelier J-2 sprint-021 application runbook OPS-PREP-J0 mais décision finale (A go / B Out) **non actée dans review/retro doc** ce sprint. | Sprint-022 atelier OPS-PREP J-2 doit acter explicitement (matrice runbook §3) + capturer décision dans sprint-022 review. |
| L-3 | **Tests Functional/Integration suite failures pré-existantes** (DB schema sync) — 117 failures bandeau. Migration `Version20260510170000` (US-101) non auto-appliquée test DB. | Sprint-022 BUFFER : run `doctrine:schema:create` ou `migrations:migrate` test DB en CI bootstrap (script fixture pre-test). |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Recalibrer baseline vélocité sprint-022 à 12 pts ferme** (vs 17 sprint-021 exception). Sprint-021 = sprint exceptionnel driven par décisions PO Q1.1 grille hebdo (+3 pts) + Q3.1 4 états (+2 pts). | Évite holdover sprint-022 si décisions PO sprint-022 moins denses. ADR-0016 trigger réversibilité Q6.1 acte explicite. |
| S-2 | **Mesurer coverage post-merge sprint-021** (Doctrine sample script ou CI report) avant kickoff sprint-022 cible step 11. Sprint-020 retro indiquait 65 % : valider chiffres réels post 6 PRs sprint-021. | Métrique factuelle vs estimation. Informe scope TEST-COVERAGE-011 sprint-022. |
| S-3 | **Symfony Workflow YAML config sprint-022 si UI/integration besoin** (composer require symfony/workflow). Sprint-021 livre Domain state machine via `WorkItemStatus::canTransitionTo` + `markAsXxx()` méthodes — Symfony Workflow optionnel pour UI/listeners visuels. | Évaluer ROI sprint-022 selon demande PO (dashboard workflow UI ?). Si non utile, garder Domain-only. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Engagement ferme > 12 pts sans circumstances exceptionnelles documentées** (décision PO atelier explicite + ROI cap libre déjà alloué). Sprint-021 = exception PO accepta capacité 17 pts. Sprint-022+ ne pas reproduire défaut. | Vélocité 12 pts moyenne réaliste sprints 014-016 (3 sprints consécutifs 100 %). Au-delà = risque holdover + qualité dégradée. |
| ST-2 | **Tests Integration Docker DB déférés systématiquement** sprint-021 US-100/101/102/AUDIT-DAILY-HOURS (notes "reportés post-merge"). 4 stories sans test Integration livrées. | Sprint-022 BUFFER 1-2 pts pour rattrapage Integration tests sprint-021 (DoctrineEmploymentPeriodAdapter + WorkflowTransition + WeeklyTimesheetController + AuditDailyHours). |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Refactor proactif petit (sprint-021 SlackAlertingInterface 5 min) pour débloquer tests** = pattern bon. Sprint-022+ : continuer refactor incrémental quand bloqueur testabilité émerge. | Backlog clean qualité sans dette refactor batch. |
| M-2 | **Documentation décisions runtime (notes en code US-099/100/101 référant ADR-0016 + AT-3)** : traçabilité décisions PO immédiate. Pattern sprint-022+ pour stories avec décisions PO multiples. | Onboarding facilité + audit trail vivant. |
| M-3 | **Pré-vérification dépendances J-2 (AT-3)** : pattern Tech Lead audit code existant avant kickoff. Sprint-022+ étendre à toutes stories ≥ 3 pts. | Évite estimation surprise mid-sprint. |

---

## 🎯 Actions concrètes Sprint 022

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Recalibrer engagement ferme sprint-022 → 12 pts (baseline réaliste) | PO + Tech Lead | Sprint-022 Planning P1 |
| A-2 | Mesurer coverage actuel post sprint-021 (CI report ou local PHPUnit --coverage) | Tech Lead | Sprint-022 J-1 |
| A-3 | Atelier OPS-PREP-J0 J-2 sprint-022 (runbook §2 — 6 questions × stories candidates) | PO + Tech Lead | Sprint-022 J-2 |
| A-4 | Décision OPS-DECISION-B sub-epic B holdover (A go / B Out backlog) actée explicitement | PO + Tech Lead | Sprint-022 J0 atelier |
| A-5 | BUFFER 1-2 pts sprint-022 : rattrapage tests Integration sprint-021 (4 composants déférés) | Tech Lead | Sprint-022 fin |
| A-6 | Métrique « 0 holdover OPS » suivie sprint-022 retro (cible runbook) | Tech Lead | Sprint-022 retro |
| A-7 | Décision PO sprint-022 scope : UC `CalculateProjectMargin` complet (ADR-0016 A-8) + refactor `AlertDetectionService` legacy + Coverage step 11 + Sub-epic B OPS si A acté | PO | Sprint-022 Planning P1 |

---

## 📊 Directive Fondamentale

> « Indépendamment de ce que nous découvrons aujourd'hui, nous comprenons et
> croyons sincèrement que chacun a fait du mieux qu'il pouvait, étant donné
> ce qui était connu à ce moment-là, ses compétences et capacités, les
> ressources disponibles et la situation rencontrée. »

---

## 🚀 Sprint-021 takeaway

**EPIC-003 Phase 3 livré complet** : UC RecordWorkItem + Workflow + UI grille
hebdo + alerte marge Slack + audit data quality. Phase 3 → production ready
dès Render redeploy + correction admin AUDIT-DAILY-HOURS post-output.

**Recalibrage vélocité réussi à 117 %** (20 pts livrés / 17 ferme +
3 libre). Point haut historique 12 sprints. Exception driven par décisions
PO atelier (UI grille hebdo + Workflow 4 états).

**0 holdover OPS sub-epic B** = première occurrence depuis sprint-016
(4 sprints holdover précédents 017→020). Runbook OPS-PREP-J0 livré
sprint-020 + appliqué sprint-021 J-2 = correctif structurel effectif.

**Pattern maturité confirmée** : strangler fig (6 applications sprint-021),
TDD Domain pure (50+ tests host PHP), atelier décisions amont (ADR-0016 J0),
pré-vérification dépendances (AT-3 J-2), refactor proactif testabilité
(SlackAlertingInterface).

**Risk visible sprint-022** :
- Vélocité 17 pts ≠ baseline durable (recalibrage 12 pts trigger
  réversibilité ADR-0016 Q6.1 — voir A-1)
- Tests Integration Docker DB déférés 4 composants (BUFFER A-5)
- Migration test DB sync (Functional suite failures pré-existantes)
- OPS-DECISION-B sub-epic B holdover décision finale non actée explicit

---

## 🔗 Liens

- Sprint-021 review : `sprint-review.md`
- Sprint-020 retro : `../sprint-020-epic-003-phase-2-acl/sprint-retro.md`
- Sprint-022 kickoff : `../sprint-022-epic-003-phase-3-completion/sprint-goal.md`
- ADR-0016 EPIC-003 Phase 3 décisions
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
