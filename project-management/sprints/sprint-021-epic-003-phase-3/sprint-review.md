# Sprint Review — Sprint 021

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 021 — EPIC-003 Phase 3 RecordWorkItem + Workflow + UI grille hebdo |
| Date | 2026-05-10 (clôture anticipée — sprint J0+J1 compactés) |
| Sprint Goal | UC RecordWorkItem invariant journalier flexible + DailyHoursValidator + Workflow Symfony 4 états + UI Twig grille hebdo + capacité libre alerte marge Slack + 0 holdover OPS |
| Capacité | 17 pts ferme + 3 pts libre = 20 pts |
| Engagement ferme | 17 pts |
| Capacité libre | 3 pts (Q6.4 MarginThresholdExceededEvent) |
| Livré | **20 pts (117 % capacité ferme)** |

---

## 🎯 Sprint Goal — Atteint intégralement ✅

**Goal** : « EPIC-003 Phase 3 livraison complète : UC `RecordWorkItem` avec
invariant journalier flexible (warning override), Domain Service
`DailyHoursValidator`, Workflow Symfony 4 états (`draft → validated →
billed → paid`) avec validation role-based managers, UI Twig grille hebdo
saisie + auto-save. Capacité libre `MarginThresholdExceededEvent` + alerte
Slack 10 % marge. Application stricte runbook OPS-PREP-J0 J-2 → 0 holdover
OPS sub-epic B sprint-021. »

**Résultat 100 %** :
- ✅ UC `RecordWorkItem` opérationnel (champs Q1.2 + step Q1.3 + auto-save Q2.1 + lock Q2.2 + admin override Q2.3 + warning Q2.4 + role-based Q3.2)
- ✅ `DailyHoursValidator` Domain Service + ACL adapter `EmploymentPeriod` (AT-3.1)
- ✅ `WorkItemStatus` enum 4 états + transitions Aggregate + `WorkItemValidatedEvent` + `WorkItemBilledEvent` + `WorkItemPaidEvent`
- ✅ `BillRelatedWorkItemsOnInvoiceCreated` listener cross-aggregate (AT-3.2 payload `array<WorkItemId>`)
- ✅ UI Twig grille hebdo `/timesheet/week/{week}` + Stimulus auto-save + warning modal
- ✅ AUDIT-DAILY-HOURS extension audit script + AT-3.4/3.5 SET TRANSACTION READ ONLY
- ✅ `MarginThresholdExceededEvent` Domain + handler async Slack alerting (capacité libre)
- ✅ **0 holdover OPS sub-epic B** — métrique cible runbook OPS-PREP-J0 atteinte ✅

---

## 📦 User Stories Livrées

| Story | Pts | PR | Sub-epic |
|---|---:|---|---|
| US-100 DailyHoursValidator + EmploymentPeriod ACL adapter | 2 | #216 | A |
| US-099 UC RecordWorkItem + WorkItemStatus + role-based managers | 5 | #217 | A |
| US-101 Workflow 4 états + Invoice billing listener | 4 | #218 | A |
| US-102 UI Twig grille hebdo + Stimulus auto-save | 5 | #219 | B |
| AUDIT-DAILY-HOURS audit script + READ ONLY | 1 | #220 | C |
| US-103 MarginThresholdExceededEvent + alerte Slack | 3 | #221 | Libre Q6.4 |
| **Total** | **20** | | **17/17 ferme + 3/3 libre = 117 %** |

### Pas de holdover sprint-022

Première occurrence sprint sans holdover OPS depuis sprint-016 (sprints 017→020 = 4 sprints holdover Sub-epic B). Runbook OPS-PREP-J0 sprint-020 livré + appliqué sprint-021 J-2 = mécanisme correctif effectif.

---

## 📈 Métriques

| Métrique | Valeur | Tendance |
|---|---|---|
| Points engagés ferme | 17 | recalibrage continu — challenge |
| Points livrés | 20 | +9 vs sprint-020 (9) |
| Vélocité | 20 | +100 % vs moyenne 11 sprints (10.4) |
| Taux complétion ferme | 117 % | recalibrage challenge acté PO |
| ADR publiés | 1 (ADR-0016) | continuité |
| Stories spécifiées | 5 (US-099..US-103) | + AUDIT-DAILY-HOURS |
| Tests Unit ajoutés | ~50+ | 1105 tests verts main |
| PHPStan max | 0 erreur | maintenu |
| Composer audit | 0 vulnérabilité | maintenu |
| Bugs découverts | 0 | stable |
| Coverage estimé | 65 % | step 11 sprint-022 cible 68 % |

### Vélocité historique 12 sprints

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
| **021** | **17** | **20** | **117 %** |

Vélocité moyenne 12 sprints : ~11 pts. Sprint-021 = 20 pts livrés, point haut historique.

**Risque flagged ADR-0016 trigger réversibilité Q6.1** : sprint-022 doit
recalibrer baseline vers vélocité réaliste (12 pts max ferme) — sprint-021
est exception driven par décisions PO (UI grille hebdo +3 pts vs reco +
Workflow 4 états +2 pts +). Voir retro action A-1.

---

## 🎯 Démonstration

### EPIC-003 Phase 3 livré complet
1. UC `RecordWorkItem` saisie WorkItem avec invariant journalier flexible
2. Workflow Symfony state machine 4 états + cross-aggregate Invoice listeners
3. UI grille hebdo drag-drop auto-save + warning override modal
4. Audit data prod read-only (`--audit-daily-hours` flag + SET TRANSACTION READ ONLY)

### MarginThresholdExceededEvent + Slack alerting
- Pure Domain Event (`isCritical()` helper)
- Handler async Symfony Messenger
- `SlackAlertingInterface` extracted pour mockabilité tests
- Severity CRITICAL si margin < threshold/2 sinon WARNING
- Dégradé silent si webhook URL absent (option B atelier AT-1)

### Pattern strangler fig appliqué 6 fois sprint-021
- `EmploymentPeriod` ACL adapter (US-100)
- WorkItem `status` field via `reconstitute extra` (US-099)
- `InvoiceCreatedEvent` payload extension backward compat (US-101 AT-3.2)
- `MarginThresholdExceededEvent` coexiste `LowMarginAlertEvent` legacy (US-103 AT-3.3)
- UI `/timesheet/week/*` coexiste legacy `TimesheetController` (US-102)
- Migration `timesheets.status` default 'draft' rows existantes (US-101)

---

## 💬 Feedback PO (à recueillir)

Questions Sprint Planning P1 sprint-022 :
1. Recalibrage vélocité sprint-022 — baseline 12 pts ferme (vs 17 sprint-021 exception) ?
2. UC `CalculateProjectMargin` complet sprint-022 (ADR-0016 A-8) — caller dispatch event Q6.4 livré sprint-021
3. Refactor `AlertDetectionService` legacy → dispatch nouveau `MarginThresholdExceededEvent` + `@deprecated` `LowMarginAlertEvent` (AT-3.3 sprint-022)
4. Coverage step 11 (65 → 68 %) cible : Domain Notification + Settings BCs ?
5. OPS-DECISION-B sprint-021 atelier J-2 — décision finale sub-epic B holdover ?
   - Si A confirmé J0 : Slack webhook prod + Sentry alerts + SMOKE config = 1 pt sprint-022
   - Si B Out backlog : retiré EPIC-002 stragglers, replan sprint dédié OPS quand owner aligné
6. Configurabilité hiérarchique seuil marge Q5.1 (D) sprint-023+ confirmé ?

---

## 🔗 Liens

- PRs sprint-021 : #216 #217 #218 #219 #220 #221
- ADR-0016 EPIC-003 Phase 3 décisions : `docs/02-architecture/adr/0016-epic-003-phase-3-decisions.md`
- Atelier OPS-PREP-J0 screening : `atelier-ops-prep-j-2-screening.md`
- AT-3 dependencies findings : `at-3-dependencies-findings.md`
- Runbook OPS-PREP-J0 (livré sprint-020) : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- Sprint-020 review : `../sprint-020-epic-003-phase-2-acl/sprint-review.md`
- Sprint-022 kickoff : `../sprint-022-epic-003-phase-3-completion/sprint-goal.md`
