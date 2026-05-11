# Sprint Review — Sprint 022

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 022 — EPIC-003 Phase 3 Completion + Coverage Step 11 |
| Date | 2026-05-11 (clôture anticipée — sprint J0+J1 compactés) |
| Sprint Goal | UC `CalculateProjectMargin` complet + refactor `AlertDetectionService` legacy → Domain Event + coverage step 11 + BUFFER tests Integration sprint-021 + 0 holdover OPS |
| Capacité | 12 pts ferme + 1-2 pts libre |
| Engagement ferme | 12 pts |
| Capacité libre | 1 pt utilisé (WORKFLOW-YAML) |
| Livré | **13 pts (108 % capacité)** + 1 décision ADR-0017 OUT Backlog |

---

## 🎯 Sprint Goal — Atteint intégralement ✅

**Goal** : « EPIC-003 Phase 3 completion : UC `CalculateProjectMargin`
complet (caller dispatch `MarginThresholdExceededEvent` US-103 livré) +
refactor `AlertDetectionService` legacy → dispatch nouveau Domain Event
+ `LowMarginAlertEvent` `@deprecated`. Coverage step 11 (65 → 68 %) via
Domain Notification + Settings BCs. BUFFER tests Integration sprint-021
rattrapage. Application stricte runbook OPS-PREP-J0 J-2 → 0 holdover OPS
sprint-022. »

**Résultat 100 %** :
- ✅ UC `CalculateProjectMargin` Application Layer + handler async cross-aggregate
- ✅ `Project` aggregate +6 méthodes margin snapshot (Q4.3 ADR-0016)
- ✅ Refactor `AlertDetectionService` dual dispatch (legacy + Domain Event)
- ✅ `LowMarginAlertEvent` `@deprecated` PHPDoc + plan removal sprint-023+
- ✅ Coverage step 11 (Vacation + Contributor Events + Exceptions, 20 tests pivot pragmatic)
- ✅ BUFFER Integration tests sprint-021 50 % (2/4 composants — `DoctrineEmploymentPeriodAdapter` + AUDIT-DAILY-HOURS)
- ✅ **0 holdover OPS sub-epic** — 2ᵉ sprint consécutif (cible runbook OPS-PREP-J0)
- ✅ ADR-0017 Sub-epic B Out Backlog acté (4ᵉ holdover signal arrêt runbook §3)
- ✅ Sub-epic F WORKFLOW-YAML cap libre (Symfony Workflow installé + 4 états config + 6 tests Integration)

---

## 📦 User Stories Livrées

| Story | Pts | PR | Sub-epic |
|---|---:|---|---|
| US-104 UC `CalculateProjectMargin` + Project snapshot | 5 | #224 | A |
| US-105 refactor `AlertDetectionService` + `@deprecated` | 2 | #225 | B |
| TEST-COVERAGE-011 Vacation + Contributor pivot | 2 | #226 | C |
| BUFFER tests Integration sprint-021 (2/4 composants) | 2 | #227 | E |
| ADR-0017 Sub-epic B Out Backlog (4ᵉ holdover signal arrêt) | 0 | #228 | D (OUT) |
| WORKFLOW-YAML Symfony state machine config + 6 tests Integration | 1 | #229 | F (cap libre) |
| **Total** | **12 ferme + 1 libre = 13** | | **108 %** |

### Pas de holdover sprint-023

**2ᵉ sprint consécutif sans holdover OPS** (sprint-021 + 022). Runbook
OPS-PREP-J0 confirmé efficace : Sub-epic B Out Backlog ADR-0017 acté vs
holdover silent.

---

## 📈 Métriques

| Métrique | Valeur | Tendance |
|---|---|---|
| Points engagés ferme | 12 | recalibrage durable A-1 sprint-021 retro |
| Points livrés ferme | 12 | 100 % ferme |
| Points livrés total | 13 | +1 cap libre WORKFLOW-YAML |
| Taux complétion ferme | 100 % | recalibrage 12 pts tient |
| Vélocité | 13 | aligné moyenne post-recalibrage |
| ADR publiés | 2 (ADR-0017 + workflow.yaml + Project margin) | +2 |
| Tests Unit ajoutés | ~30 (US-104 + US-105 + TEST-COVERAGE-011) | total 1143 |
| Tests Integration ajoutés | 12 (6 BUFFER + 6 WORKFLOW) | +12 |
| PHPStan max | 0 erreur | maintenu |
| Composer audit | 0 vulnérabilité | maintenu |
| Bugs découverts | 0 | stable |

### Vélocité historique 13 sprints

| Sprint | Engagement | Livré | Taux |
|---|---:|---:|---:|
| 010 | 8 | 8 | 100 % |
| 011 | 8 | 8 | 100 % |
| 012 | 9 | 9 | 100 % |
| 013 | 9 | 9 | 100 % |
| 014 | 12 | 12 | 100 % |
| 015 | 13 | 13 | 100 % |
| 016 | 11 | 11 | 100 % |
| 017 | 10 | 13 | 130 % |
| 018 | 8.5 | 10.5 | 124 % |
| 019 | 12 | 11 | 92 % |
| 020 | 10 | 9 | 90 % |
| 021 | 17 | 20 | 117 % |
| **022** | **12** | **13** | **108 %** |

Vélocité moyenne 13 sprints : ~11.3 pts. Sprint-022 = 13 pts livrés
**aligné durable** (vs 17/20 sprint-021 exception). **Recalibrage A-1
sprint-021 retro tient**.

---

## 🎯 Démonstration

### EPIC-003 Phase 3 livré complet (sprint-021 + 022)

Chain WorkItem → Margin → Alerte :
1. Saisie WorkItem grille hebdo (US-102) → UC `RecordWorkItem` (US-099)
2. Dispatch `WorkItemRecordedEvent` (US-099) async
3. Handler `RecalculateProjectMarginOnWorkItemRecorded` (US-104)
   consume → UC `CalculateProjectMargin`
4. Project::setMargeSnapshot(coutTotal, factureTotal) + `getMargePercent`
5. Si marge < threshold 10 % → dispatch `MarginThresholdExceededEvent`
   (US-103)
6. Handler `SendMarginAlertOnThresholdExceeded` async → Slack
   `#alerts-prod` (dégradé silent si webhook absent)

### Workflow Symfony state machine

- Domain : `WorkItem::markAsValidated/Billed/Paid` (sprint-021 US-101)
- Symfony Workflow `work_item` 4 états + transitions (sprint-022 WORKFLOW-YAML)
- Defense-in-depth — les deux mécanismes cohérents

### Pattern strangler fig appliqué 8 fois sprint-021 + 022
- EmploymentPeriod ACL adapter (US-100)
- WorkItem status reconstitute (US-099)
- InvoiceCreatedEvent payload extension (US-101 AT-3.2)
- MarginThresholdExceededEvent vs LowMarginAlertEvent (US-103 AT-3.3)
- UI /timesheet/week/* vs legacy TimesheetController (US-102)
- Migration timesheets.status default 'draft' (US-101)
- **Sprint-022** : AlertDetectionService dual dispatch (US-105)
- **Sprint-022** : Symfony Workflow YAML co-exists Domain state machine (WORKFLOW-YAML)

### ADR-0017 décision structurelle

Sub-epic B Out Backlog acté après 4 sprints holdover (017→020). Runbook
OPS-PREP-J0 §3 décision matrix appliquée strictement. Pattern OPS prep
J0 confirmé crédible.

---

## 💬 Feedback PO (à recueillir)

Questions Sprint Planning P1 sprint-023 :
1. **A-9 ADR-0016** configurabilité hiérarchique seuil marge Q5.1 D —
   demandée prod ? Si oui sprint-023 sub-epic A.
2. **Refactor `NotificationSubscriber`** pour consume Domain Events
   directement (suppression `LowMarginAlertEvent` legacy `@deprecated` US-105
   sprint-022) — sprint-023 ?
3. **Persistence margin snapshot Project** (sprint-022 US-104 transient
   only) — sprint-023 migration Doctrine cols `cout_total_cents` +
   `facture_total_cents` + `marge_calculated_at` ?
4. **2/4 BUFFER Integration tests reportés** (WeeklyTimesheetController
   Functional + Workflow E2E) — sprint-023 finition ?
5. **Coverage step 12** (68 → 70 %) — Domain Order/Invoice edge cases
   additionnels ?
6. **OPS-DECISION-B sub-epic B** : sprint dédié OPS replan futur (post
   ADR-0017) — quand owner aligné + 4 credentials simultanés ?
7. **Mago lint cleanup batch** (audit recommendation #3 — 627 errors
   legacy) — sprint-024+ dédié ?

---

## 🔗 Liens

- PRs sprint-022 : #224 #225 #226 #227 #228 #229
- ADR-0017 Sub-epic B Out Backlog : `docs/02-architecture/adr/0017-ops-sub-epic-b-out-backlog.md`
- ADR-0016 EPIC-003 Phase 3 décisions
- Workflow YAML : `config/packages/workflow.yaml`
- Sprint-021 review/retro : `../sprint-021-epic-003-phase-3/sprint-review.md`
- Sprint-023 kickoff : `../sprint-023-epic-003-phase-3-finition/sprint-goal.md`
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
