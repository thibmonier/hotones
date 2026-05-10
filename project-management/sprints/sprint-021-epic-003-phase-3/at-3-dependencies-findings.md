# AT-3 Dependencies Findings — Sprint-021

| Champ | Valeur |
|---|---|
| Sprint | 021 EPIC-003 Phase 3 |
| Date | 2026-05-10 (J-2) |
| Owner | Tech Lead |
| Origine | Atelier OPS-PREP-J0 J-2 décision AT-3 |

---

## 1. Synthèse

| Dépendance | Statut | Action sprint-021 |
|---|---|---|
| `EmploymentPeriod` interface Domain | ❌ **N'EXISTE PAS** | Créer `EmploymentPeriodRepositoryInterface` Domain + ACL adapter wrapping flat repo |
| `InvoiceCreatedEvent` Domain | ✅ existe `src/Domain/Invoice/Event/InvoiceCreatedEvent.php` | Réutiliser direct US-101 |
| `InvoicePaidEvent` Domain | ✅ existe `src/Domain/Invoice/Event/InvoicePaidEvent.php` | Réutiliser direct US-101 |
| `WorkItemRepositoryInterface::findByContributorAndDate` | ✅ existe (ADR-0015 A-2 livré sprint-020 #207) | Réutiliser direct US-099 |
| `WorkItemStatus` enum + champ `status` Aggregate | ❌ **N'EXISTE PAS** | Créer enum + champ Phase 3 US-101 |
| `WorkItem` events (`Validated`, `Billed`, `Paid`) | ❌ **MANQUANT** (existe `WorkItemRecordedEvent` + `WorkItemRevisedEvent`) | Créer 3 events Phase 3 US-101 |
| `MarginThresholdExceededEvent` Domain | ❌ **N'EXISTE PAS** (legacy `App\Event\LowMarginAlertEvent` existe) | Créer pure Domain event US-103 |
| `SlackAlertingService` | ✅ existe (US-094 sprint-017 #189) | Réutiliser direct US-103 |

---

## 2. Détails

### 2.1 EmploymentPeriod (US-100 dépendance)

**État actuel** :
- ✅ Flat entity `src/Entity/EmploymentPeriod.php` (Doctrine ORM)
- ✅ Doctrine repo `src/Repository/EmploymentPeriodRepository.php`
- ❌ Pas de Domain interface ni Domain entity
- Champs `weeklyHours` + `workTimePercentage` à confirmer (sprint-021 AUDIT-DAILY-HOURS scan)

**Pattern Phase 3 recommandé** : ACL adapter (vs Domain entity migration complète)

```php
// Domain
interface EmploymentPeriodRepositoryInterface
{
    public function findActiveForContributor(
        ContributorId $contributorId,
        \DateTimeImmutable $date
    ): ?EmploymentPeriodSnapshot;
}

// Domain DTO (snapshot)
final readonly class EmploymentPeriodSnapshot
{
    public function __construct(
        public WorkedHours $weeklyHours,
        public WorkTimePercentage $workTimePercentage,
    ) {}
}

// Infrastructure ACL adapter
final readonly class DoctrineEmploymentPeriodAdapter implements EmploymentPeriodRepositoryInterface
{
    public function __construct(
        private \App\Repository\EmploymentPeriodRepository $flatRepo
    ) {}

    public function findActiveForContributor(...): ?EmploymentPeriodSnapshot
    {
        $flat = $this->flatRepo->findActiveForContributor(...);
        if (!$flat) return null;
        return new EmploymentPeriodSnapshot(...);
    }
}
```

**Impact estimation US-100** : +0.5-1 pt (création interface + DTO + ACL adapter).
Reste 2 pts US-100 dans budget. À confirmer décomposition tasks Sprint Planning P2.

**Justification** : pattern strangler fig sprint-008-013 — Domain interface + ACL adapter sans toucher legacy. Migration `EmploymentPeriod` Domain pure sprint-026+ si besoin.

---

### 2.2 Invoice events (US-101 dépendance)

**État actuel** : ✅ tous existants pure Domain
- `src/Domain/Invoice/Event/InvoiceCreatedEvent.php`
- `src/Domain/Invoice/Event/InvoicePaidEvent.php`
- Bonus : `InvoiceIssuedEvent` + `InvoiceCancelledEvent` (utilisables sprint-022+ si besoin transitions WorkItem supplémentaires)

**Action US-101** : créer Application Layer listeners ACL (`Invoice` BC → `WorkItem` BC) :
- `BillRelatedWorkItemsOnInvoiceCreated` — consume `InvoiceCreatedEvent` → trigger workflow `bill` sur WorkItems projet
- `MarkRelatedWorkItemsAsPaidOnInvoicePaid` — consume `InvoicePaidEvent` → trigger workflow `mark_paid`

**Risk** : couplage cross-aggregate à valider (Invoice → WorkItem). Le listener doit charger WorkItems via repo + appliquer transition workflow + sauvegarder. Latence acceptable (async via Symfony Messenger) — pattern Q4.1 réutilisable.

⚠️ **Question ouverte** : `InvoiceCreatedEvent` contient-il les `WorkItem` impactés ? Si non, listener devra query `WorkItemRepository` par `Project.id`. Confirmer payload event Sprint Planning P2.

---

### 2.3 WorkItem state machine 4 états (US-101 cœur)

**État actuel** :
- Aggregate `src/Domain/WorkItem/Entity/WorkItem.php` sprint-019 #200
- Pas de champ `status` ni `WorkItemStatus` enum
- Events existants : `WorkItemRecordedEvent` + `WorkItemRevisedEvent`

**À créer Phase 3 US-101** :

1. `WorkItemStatus` enum Domain :
   ```php
   enum WorkItemStatus: string
   {
       case DRAFT = 'draft';
       case VALIDATED = 'validated';
       case BILLED = 'billed';
       case PAID = 'paid';
   }
   ```

2. Champ `WorkItemStatus $status` sur `WorkItem` aggregate + getter
3. 3 events Domain :
   - `WorkItemValidatedEvent`
   - `WorkItemBilledEvent`
   - `WorkItemPaidEvent`
4. Méthodes Aggregate Root pour transitions (avec garde + record event) :
   - `markAsValidated(): void`
   - `markAsBilled(): void`
   - `markAsPaid(): void`
5. Migration Doctrine : ajout colonne `status` table `work_item` (default `'draft'` pour rows existantes)
6. Workflow Symfony config `config/packages/workflow.yaml`

**Impact estimation US-101** : 4 pts inchangé acceptable (estimation ADR-0016 incluait ces ajouts).

---

### 2.4 MarginThresholdExceededEvent (US-103 cœur)

**État actuel** :
- ❌ Pas de Domain Event `MarginThresholdExceededEvent`
- ✅ Legacy `App\Event\LowMarginAlertEvent` existe (NotificationEvent couplé `Project` flat)
- ✅ Legacy `AlertDetectionService::*` (`src/Service/AlertDetectionService.php`) dispatche `LowMarginAlertEvent` ligne 120

**Décision Phase 3 US-103** : pattern strangler fig
- **Créer** pure Domain `MarginThresholdExceededEvent` côté `src/Domain/Project/Event/`
- **Coexister** avec legacy `LowMarginAlertEvent` (pas de refactor `AlertDetectionService` sprint-021)
- **Future sprint** (sprint-022+) : refactor `AlertDetectionService` pour dispatcher Domain event + supprimer legacy event si plus utilisé

**Spec Domain Event** :
```php
// src/Domain/Project/Event/MarginThresholdExceededEvent.php
final readonly class MarginThresholdExceededEvent implements DomainEventInterface
{
    public function __construct(
        public ProjectId $projectId,
        public Money $costTotal,
        public Money $invoicedPaidTotal,
        public float $marginPercent,
        public float $thresholdPercent,
        public \DateTimeImmutable $occurredOn = new \DateTimeImmutable(),
    ) {}
}
```

**Handler async** : `SendMarginAlertOnThresholdExceeded` consume event → `SlackAlertingService` (US-094) post `#alerts-prod` channel.

**Dedup logique 24h** : table `margin_alert_log` ou cache Redis avec TTL 24h pour skipper alertes même projet.

⚠️ **Question Sprint Planning P2** : co-existence `LowMarginAlertEvent` + `MarginThresholdExceededEvent` accepté SI calculs marge cohérents (sinon risk alertes contradictoires). À vérifier / décider.

---

### 2.5 BDD prod read-only (AUDIT-DAILY-HOURS dépendance)

**État actuel** : à confirmer Tech Lead.

**Vérifications nécessaires** :
- Render Postgres production : credential read-only existant ?
- Variable env `DATABASE_URL_READONLY` configurée ? OU read-only via filtres Doctrine sur user prod ?
- Script `app:audit:contributors-cjm` se connecte via `DATABASE_URL` (read-only suffit pour SELECT)

**Risk si pas confirmé** : audit script lance écriture (DDL/DML accidentel). Seuil acceptable : exécution monitorée + transaction en mode SELECT only avec `set transaction read only` PostgreSQL.

**Action Sprint Planning P2** : ajouter assert au script début (`SET TRANSACTION READ ONLY`) pour garantie.

---

## 3. Décisions actées Sprint Planning P2 (2026-05-10)

| ID | Question | Décision | Impact |
|---|---|---|---|
| **AT-3.1** | EmploymentPeriod : Domain interface + ACL adapter OU Domain entity migration complète ? | ✅ **ACL Adapter** | Pattern strangler fig sprint-008-013 réutilisé. US-100 = 2-3 pts (création interface + DTO snapshot + ACL adapter wrapping flat repo). Migration Domain pure sprint-026+ si besoin. |
| **AT-3.2** | `InvoiceCreatedEvent` payload contient WorkItem IDs OU listener query par Project.id ? | ✅ **WorkItem IDs in payload** | Étendre `InvoiceCreatedEvent` constructor avec `array<WorkItemId> $workItemIds = []`. Caller (Application Layer use case `CreateInvoice`) collecte WorkItems projet AVANT dispatch event. Listener consume payload directement (pas de query DB extra). Backward compatible : default empty array. |
| **AT-3.3** | `MarginThresholdExceededEvent` co-existence avec legacy `LowMarginAlertEvent` accepté ? | ✅ **Oui** + `LowMarginAlertEvent` marqué `@deprecated` | Co-existence sprint-021 (strangler fig). Legacy event marqué `@deprecated` PHPDoc + plan retrait sprint-022+ après refactor `AlertDetectionService` vers Domain Event. Pas de break consumers actuels. |
| **AT-3.4** | BDD prod read-only access confirmé pour AUDIT-DAILY-HOURS ? | ✅ **Option C : SET TRANSACTION READ ONLY** | Defense-in-depth Postgres-level via SQL `SET TRANSACTION READ ONLY` au début script. Pas de nouveau DB user (option B reportée sprint-022+ si autres scripts audit ajoutés). Cost ~1 ligne SQL. |
| **AT-3.5** | Script audit `--audit-daily-hours` utilise `SET TRANSACTION READ ONLY` ? | ✅ **Oui implémenter** | Pattern reproductible scripts audit futurs. Test Integration Docker DB validera qu'écriture lance exception PG. Étendre AUDIT-CONTRIBUTORS-CJM existant sprint-020 #205 avec même pattern (refactor optionnel sprint-022+). |

---

## 4. Impact estimations sprint-021

| Story | Estimation ADR-0016 | Impact AT-3 findings | Estimation finale |
|---|---:|---|---:|
| US-099 | 5 pts | OK (trouvé `findByContributorAndDate` déjà ADR-0015 A-2) | 5 |
| US-100 | 2 pts | +0.5-1 pt EmploymentPeriod ACL adapter | **2-3** |
| US-101 | 4 pts | OK (Invoice events trouvés) — couplage cross-aggregate à monitorer | **4** |
| US-102 | 5 pts | OK | 5 |
| AUDIT-DAILY-HOURS | 1 pt | +0 pt (READ-ONLY confirmation rapide) | 1 |
| **Sub-total ferme** | **17** | | **17-18** |
| US-103 (libre) | 2-3 pts | OK (pure Domain event + handler + SlackAlertingService réutilisé) | 2-3 |

**Risk** : si EmploymentPeriod ACL ajoute 1 pt → 18 pts ferme. Vélocité +80 % vs moyenne 10. ADR-0016 trigger réversibilité §Q6.1 : si holdover > 5 pts → recalibrage sprint-022 baseline 12 pts.

**Recommandation Sprint Planning P2** : maintenir 17 pts ferme (ACL adapter overhead absorbable sub-epic A US-100), valider Sprint Planning P2 décomposition tasks détaillée.

---

## 5. Liens

- ADR-0016 EPIC-003 Phase 3 décisions : `../../../docs/02-architecture/adr/0016-epic-003-phase-3-decisions.md`
- ADR-0015 EPIC-003 Phase 2 décisions task=NULL + doublons + invariant journalier : `../../../docs/02-architecture/adr/0015-epic-003-phase-2-decisions-task-null-doublons.md`
- Atelier OPS-PREP-J0 screening : `atelier-ops-prep-j-2-screening.md`
- Sprint-021 sprint-goal : `sprint-goal.md`
- Stories US-099..US-103 : `../../backlog/user-stories/TIM.md`
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`

---

**Auteur** : Tech Lead
**Date** : 2026-05-10 (J-2 sprint-021)
**Version** : 1.0.0
**Sprint** : 021 PRE-1 atelier OPS-PREP-J0 décision AT-3
