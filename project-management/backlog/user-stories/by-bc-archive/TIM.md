# Module: Time Tracking

> **DRAFT** — stories `INFERRED`. Source: `prd.md` §5.6 (FR-TIM-01..04). Generated 2026-05-04.

---

## US-034 — Saisie de temps

> INFERRED from `Timesheet`, `TimesheetController`, `Service/Timesheet/*`.

- **Implements**: FR-TIM-01 — **Persona**: P-001 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** intervenant
**I want** saisir mon temps quotidien/hebdo par projet/tâche
**So that** mes heures sont facturables et la rentabilité calculée.

### Acceptance Criteria
```
Given intervenant sur projet
When POST /timesheets {date, projet, tâche, durée}
Then Timesheet créé statut "draft"
```
```
Given total > 24h sur un jour
Then refusé
```
```
Given week-end
Then accepté avec warning (heures sup)
```

---

## US-035 — Timer running

> INFERRED from `RunningTimer`.

- **Implements**: FR-TIM-02 — **Persona**: P-001 — **Estimate**: 3 pts — **MoSCoW**: Should

### Card
**As** intervenant
**I want** démarrer/arrêter un timer en temps réel
**So that** je n'oublie pas de saisir.

### Acceptance Criteria
```
When POST /timer/start {projet, tâche}
Then RunningTimer créé
```
```
When POST /timer/stop
Then RunningTimer converti en Timesheet draft
```
```
Given timer actif > N heures
Then notification "timer toujours actif?"
```

---

## US-036 — Validation des temps

> INFERRED from `TimesheetPendingValidationEvent` + `NotificationType::TIMESHEET_PENDING_VALIDATION`.

- **Implements**: FR-TIM-03 — **Persona**: P-001, P-002, P-003 — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** chef de projet / manager
**I want** valider/refuser les timesheets soumis
**So that** la facturation et la rentabilité reposent sur du temps validé.

### Acceptance Criteria
```
Given intervenant soumet semaine
When POST /timesheets/{id}/submit
Then statut "pending_validation" + TimesheetPendingValidationEvent
And notification au manager
```
```
Given manager
When approve
Then statut "approved", verrouillé en modification
```
```
Given refus
Then statut "rejected" + commentaire requis
And notification à l'intervenant
```

---

## US-037 — Détection timesheet hebdo manquant

> INFERRED from `NotificationType::TIMESHEET_MISSING_WEEKLY`.

- **Implements**: FR-TIM-04 — **Persona**: P-001, P-002 — **Estimate**: 3 pts — **MoSCoW**: Must

### Card
**As** intervenant (et son manager)
**I want** un rappel automatique si je n'ai pas saisi en fin de semaine
**So that** la collecte de temps est exhaustive.

### Acceptance Criteria
```
Given intervenant actif
When vendredi soir + temps hebdo < seuil minimal
Then notification TIMESHEET_MISSING_WEEKLY
```
```
Given intervenant en congé approuvé
Then pas de rappel
```

### Technical Notes
- Job scheduler (`Schedule.php`)
- Tient compte des congés (FR-VAC)

---

## US-099 — UC `RecordWorkItem` avec invariant journalier flexible

- **Implements**: EPIC-003 Phase 3 — **Persona**: P-001 (intervenant) + P-002 (manager) — **Estimate**: 5 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** intervenant (P-001) ou manager (P-002)
**I want** saisir mes heures travaillées par projet (et tâche optionnellement) avec validation invariant journalier flexible
**So that** mon WorkItem DDD est créé en `draft` (ou `validated` si je suis manager), avec audit trail traçable et possibilité d'override warning seuil journalier.

### Acceptance Criteria

```
Given intervenant authentifié sur projet existant
When POST UC RecordWorkItem {date, projetId, hours, taskId?, comment?, userOverride: false}
Then WorkItem créé status "draft"
And dailyTotal pour (contributorId, date) recalculé
And WorkItemRecorded event dispatché
```

```
Given user ROLE_MANAGER ou ROLE_ADMIN authentifié
When POST UC RecordWorkItem {...}
Then WorkItem créé direct status "validated" (transition workflow validate auto)
And event WorkItemRecorded + WorkItemValidated dispatchés
```

```
Given dailyTotal + hours.requested > dailyMaxHours (calculé EmploymentPeriod)
And command.userOverride = false
When POST UC RecordWorkItem
Then DailyHoursWarningException levée
And UI affiche warning + checkbox confirmation override
```

```
Given dailyTotal + hours.requested > dailyMaxHours
And command.userOverride = true (user a confirmé)
When POST UC RecordWorkItem
Then WorkItem créé (override accepté)
And AuditLog enregistre override (qui/quand/dailyTotal/dailyMaxHours/motif)
```

```
Given WorkItem existant date < 7 jours
And projet associé pas encore facturé (Invoice.status != billed/paid)
When PUT UC RecordWorkItem (édition)
Then WorkItem mis à jour
```

```
Given WorkItem existant date > 7 jours OU projet billed/paid
And user n'est PAS ROLE_ADMIN
When PUT UC RecordWorkItem (édition)
Then refus 403 Forbidden
```

```
Given user ROLE_ADMIN
When PUT UC RecordWorkItem sur WorkItem verrouillé (date > 7 jours OU projet facturé)
Then édition autorisée
And AuditLog enregistre override admin (qui/quand/avant/après)
```

### Technical Notes
- ADR-0016 Q1.2 5 champs MVP + comment optionnel + taskId optionnel (ADR-0015)
- ADR-0016 Q1.3 step heures 0.25h (15 min)
- ADR-0016 Q2.1 auto-save (pas de submit hebdo explicite)
- ADR-0016 Q2.2 B+D édition rétroactive 7 jours OU pas si projet facturé
- ADR-0016 Q2.3 admin override + audit log
- ADR-0016 Q2.4 warning override user (vs Exception bloquante reco TL)
- ADR-0016 Q3.2 role-based managers self-validate
- Domain Service `DailyHoursValidator` (US-100) injecté
- Repository `WorkItemRepositoryInterface::findByContributorAndDate` (sprint-020 #207)
- ⚠️ OQ-1 + OQ-2 ADR-0016 à valider Sprint Planning P2 (interprétation managers + manager d'un WorkItem)

---

## US-100 — Domain Service `DailyHoursValidator` + `DailyHoursWarningException`

- **Implements**: EPIC-003 Phase 3 — **Persona**: P-001 — **Estimate**: 2 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** Tech Lead
**I want** un Domain Service `DailyHoursValidator` qui calcule `dailyMaxHours` depuis `EmploymentPeriod` et détecte dépassement journalier
**So that** UC `RecordWorkItem` peut valider invariant journalier (ADR-0015) sans coupling direct à `EmploymentPeriod` repository.

### Acceptance Criteria

```
Given contributorId existant + date donnée
And EmploymentPeriod actif (date dans intervalle) avec weeklyHours = 35 et workTimePercentage = 100
When DailyHoursValidator::dailyMaxHours(contributorId, date)
Then retourne WorkedHours(7.0) (35 × 100 / 100 / 5 = 7h)
```

```
Given contributorId + date sans EmploymentPeriod actif
When DailyHoursValidator::dailyMaxHours(contributorId, date)
Then NoActiveEmploymentPeriodException levée
```

```
Given EmploymentPeriod weeklyHours = 35, workTimePercentage = 80
When DailyHoursValidator::dailyMaxHours
Then retourne WorkedHours(5.6) (35 × 80 / 100 / 5)
```

```
Given existingWorkItems totaling 6h pour (contributor, date)
And command WorkItem.hours = 2h (donc dailyTotal = 8h)
And dailyMaxHours = 7h
When DailyHoursValidator::isExceeded(contributorId, date, additionalHours)
Then retourne true (dépassement)
```

```
Given existingWorkItems totaling 5h
And command WorkItem.hours = 1h (dailyTotal = 6h)
And dailyMaxHours = 7h
When DailyHoursValidator::isExceeded
Then retourne false (pas de dépassement)
```

### Technical Notes
- ADR-0016 A-4 + ADR-0015 invariant journalier
- **AT-3.1 acté** : ACL adapter pattern. Créer `EmploymentPeriodRepositoryInterface` Domain + `EmploymentPeriodSnapshot` DTO Domain + `DoctrineEmploymentPeriodAdapter` Infrastructure wrapping flat `App\Repository\EmploymentPeriodRepository`. Migration Domain pure entity reportée sprint-026+.
- `DailyHoursWarningException` Domain — non bloquante (Q2.4) mais propagée UC pour UI override
- Tests Unit pure host PHP (sans Docker) — `EmploymentPeriodRepositoryInterface` mockée
- Tests Integration Docker DB — `DoctrineEmploymentPeriodAdapter` validé contre vraie BDD

---

## US-101 — Workflow Symfony state machine `work_item` 4 états + cross-aggregate Invoice

- **Implements**: EPIC-003 Phase 3 — **Persona**: Tech Lead + P-002 manager — **Estimate**: 4 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** Tech Lead
**I want** un Workflow Symfony state machine `work_item` à 4 états (`draft → validated → billed → paid`) avec transitions auto déclenchées par events Invoice BC
**So that** WorkItem cycle de vie complet (saisie → validation → facturation → paiement) traçable Domain + UI affiche statut propre.

### Acceptance Criteria

```
Given config/packages/workflow.yaml configuré state machine "work_item"
When WorkItem créé
Then status initial = "draft"
```

```
Given WorkItem status "draft"
And user ROLE_MANAGER ou ROLE_ADMIN déclenche transition validate
When workflow.apply(workItem, "validate")
Then status devient "validated"
And WorkItemValidatedEvent dispatché
```

```
Given WorkItem status "draft"
And transition bill tentée (skip validated)
When workflow.apply(workItem, "bill")
Then exception InvalidTransition (transitions valides définies workflow.yaml)
```

```
Given WorkItem status "validated" associé Project facturé
And InvoiceCreatedEvent dispatché pour ce Project
When listener BillRelatedWorkItems consume event
Then WorkItem.status devient "billed"
And WorkItemBilledEvent dispatché
```

```
Given WorkItem status "billed"
And InvoicePaidEvent dispatché
When listener MarkRelatedWorkItemsAsPaid consume event
Then WorkItem.status devient "paid"
And WorkItemPaidEvent dispatché
```

### Technical Notes
- ADR-0016 Q3.1 A 4 états (vs reco TL B 2 états MVP)
- ADR-0016 A-1 + A-2 + A-10
- `WorkItemStatus` enum Domain (`DRAFT`, `VALIDATED`, `BILLED`, `PAID`)
- Symfony Workflow component (déjà bundled framework)
- Cross-aggregate Application Layer listeners ACL `Invoice` → `WorkItem`
- **AT-3 vérification** : `InvoiceCreatedEvent` + `InvoicePaidEvent` existent ✅ (`src/Domain/Invoice/Event/`)
- **AT-3.2 acté** : étendre `InvoiceCreatedEvent` constructor avec `array<WorkItemId> $workItemIds = []` (default empty = backward compat). Application Layer use case `CreateInvoice` collecte WorkItems projet AVANT dispatch event. Listeners `BillRelatedWorkItemsOnInvoiceCreated` consomment payload directement (pas de query DB extra). Migration Doctrine ajoute colonne `status` table `work_item` avec default `'draft'`.
- Migration Doctrine : ajout colonne `status` table `work_item` (default `'draft'` rows existantes)
- Tests Integration Docker DB pour transitions valides + invalides

---

## US-102 — UI Twig grille hebdo saisie WorkItem

- **Implements**: EPIC-003 Phase 3 — **Persona**: P-001 intervenant + P-002 manager — **Estimate**: 5 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** intervenant (P-001) ou manager (P-002)
**I want** une grille hebdomadaire (7 jours × N projets) avec drag-drop saisie heures + auto-save
**So that** je vois ma semaine d'un coup d'œil, équilibre projets visible, et chaque modification persistée immédiatement sans bouton submit.

### Acceptance Criteria

```
Given intervenant authentifié
When GET /timesheet/{week} (week ISO 8601 ex 2026-W19)
Then page Twig affiche grille 7 jours × projets actifs intervenant
And totaux par jour + par projet + total semaine affichés
```

```
Given intervenant saisit heures dans cellule (jour, projet)
When change heures (input change event)
Then auto-save POST UC RecordWorkItem (US-099)
And cellule met à jour visuellement (loader → ✓ saved)
And total jour + total projet + total semaine recalculés
```

```
Given saisie dépasse dailyMaxHours
When auto-save tenté avec userOverride = false
Then DailyHoursWarningException reçue côté UI
And popover/modal warning + checkbox "j'accepte override" affiché
```

```
Given user check "j'accepte override"
When auto-save retry avec userOverride = true
Then WorkItem créé avec audit log override
And cellule status devient "✓ saved (override)"
```

```
Given WorkItem date > 7 jours OU projet associé Invoice.status = billed/paid
And user n'est pas ROLE_ADMIN
When intervenant clique cellule
Then cellule disabled visuellement (grise + lock icon)
And tooltip explique raison (date > 7j OU projet facturé)
```

```
Given user ROLE_ADMIN
When clique cellule verrouillée
Then édition autorisée
And badge visuel "admin override" affiché
```

### Technical Notes
- ADR-0016 Q1.1 A grille hebdo + Q1.3 A step 0.25h + Q2.1 A auto-save
- ADR-0016 Q2.2 B+D édition lock + Q2.4 B warning override + Q2.3 A admin override
- ADR-0016 A-5
- Stimulus controller pour drag-drop + auto-save (Symfony UX Turbo / Live Components)
- Tests E2E Panther (au minimum scénario nominal saisie + warning override)
- Dépendance US-099 (consume UC) — ordre exécution US-099 → US-102 figé

---

## US-103 — `MarginThresholdExceededEvent` Domain + alerte Slack `#alerts-prod`

- **Implements**: EPIC-003 Phase 3 capacité libre — **Persona**: P-002 manager + P-003 directeur — **Estimate**: 2-3 pts — **MoSCoW**: Should — **Sprint**: 021

### Card
**As** manager (P-002) ou directeur (P-003)
**I want** une alerte Slack `#alerts-prod` automatique dès qu'un projet dépasse le seuil de marge négative (< 10 %)
**So that** je détecte les dérives projet précocement (vs audit post-mortem fin projet) et peux ajuster scope/staffing.

### Acceptance Criteria

```
Given Project marge calculée < 10 % (seuil défaut MARGIN_ALERT_THRESHOLD=0.10)
When Project recalcule marge (event WorkItemRecorded handler)
Then MarginThresholdExceededEvent dispatché
And handler async consume event
```

```
Given MarginThresholdExceededEvent reçu par handler
When SlackAlertingService::send (réutilisé US-094)
Then message Slack #alerts-prod posté
And message contient : projet nom + marge % + coût total + facturé total + lien dashboard
```

```
Given MarginThresholdExceededEvent dispatché 2x consécutivement même projet
When 2ème handler exécution
Then dedup logique : alerte non re-postée si dernière alerte < 24h pour ce projet
```

```
Given Slack webhook URL non configuré (SLACK_WEBHOOK_URL vide)
When MarginThresholdExceededEvent dispatché
Then handler log warning local (pas d'exception)
And tests Unit valident comportement degraded (sans webhook)
```

### Technical Notes
- ADR-0016 Q4.x + Q5.2 seuil 10 % défaut + Q6.4 capacité libre
- ADR-0016 A-7
- Réutilise US-094 `SlackAlertingService` (sprint-017 #189)
- Configurabilité hiérarchique seuil (Q5.1 D) reportée sprint-022+ (OQ-3 default)
- **AT-3.3 acté** : nouveau Domain Event `MarginThresholdExceededEvent` (`src/Domain/Project/Event/`) co-existe avec legacy `App\Event\LowMarginAlertEvent`. Legacy event marqué `@deprecated` PHPDoc dès sprint-021 (annotation : « Deprecated since EPIC-003 Phase 3 — use `App\Domain\Project\Event\MarginThresholdExceededEvent`. Removal planned sprint-022+ after `AlertDetectionService` refactor. »). Pas de break consumers actuels.
- Strangler fig : `AlertDetectionService` legacy continue dispatcher `LowMarginAlertEvent` sprint-021. Refactor sprint-022+ pour dispatcher `MarginThresholdExceededEvent` à la place + suppression legacy event.
- ⚠️ OPS-PREP-J0 sprint-021 PRE-1 : Slack webhook URL `#alerts-prod` configuré prod J0 ?
  - 🟢 A : webhook configuré → US-103 testable end-to-end prod (✅ go)
  - 🟡 B : webhook non configuré → US-103 livré tests Unit + staging only (livraison partielle)
  - 🔴 C : US-103 OUT capacité libre, reallocate TEST-COVERAGE-011
- ⚠️ OQ-4 ADR-0016 : sprint-021 = event + alerting (UC `CalculateProjectMargin` complet sprint-022)
- Tests Integration Docker DB + Symfony Messenger transport `async_margin`

---

## Module summary

| ID | Title | FR / EPIC | Pts | MoSCoW |
|----|-------|-----------|-----|--------|
| US-034 | Saisie temps | FR-TIM-01 | 5 | Must |
| US-035 | Timer running | FR-TIM-02 | 3 | Should |
| US-036 | Validation temps | FR-TIM-03 | 5 | Must |
| US-037 | Rappel hebdo manquant | FR-TIM-04 | 3 | Must |
| US-099 | UC RecordWorkItem invariant journalier flexible | EPIC-003 Phase 3 | 5 | Must |
| US-100 | Domain Service DailyHoursValidator | EPIC-003 Phase 3 | 2 | Must |
| US-101 | Workflow Symfony 4 états + Invoice listeners | EPIC-003 Phase 3 | 4 | Must |
| US-102 | UI Twig grille hebdo saisie | EPIC-003 Phase 3 | 5 | Must |
| US-103 | MarginThresholdExceededEvent + alerte Slack | EPIC-003 Phase 3 cap libre | 2-3 | Should |
| **Total** | | | **34-35** | |
