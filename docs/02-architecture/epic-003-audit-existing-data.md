# EPIC-003 — Audit qualité données existantes (sub-epic A AUDIT-WORKITEM-DATA)

> **Sprint-019** sub-epic A (1 pt). Output : inventaire data model + risks +
> mapping vers DDD WorkItem aggregate (informe US-097 design).

## TL;DR

- **WorkItem flat n'existe pas comme entité**. Concept = `Timesheet` (heures
  déclarées) lié à `ProjectTask` (allocation projet) avec rate `cjm`/`tjm`
  côté `Contributor` ou `EmploymentPeriod`.
- **Calcul marge actuel** : `ProfitabilityService` agrège `Timesheet.hours ×
  cjm/8` (coût) vs `Timesheet.hours × tjm/8` (CA) sur période donnée.
- **Risk principal** : `cjm`/`tjm` peuvent être `NULL` côté Contributor →
  fallback `EmploymentPeriod.cjm`/`tjm` actif. Fallback peut aussi être
  `NULL` → coût/CA = 0 silencieusement → marge faussée.
- **Recommandation US-097** : DDD `WorkItem` aggregate = wrapper sur Timesheet
  enrichi des rates résolus à l'instant T (ValueObject `HourlyRate` non-null
  par construction).

---

## 1. Inventaire data model existant

### 1.1 Entités impliquées

```
Project (flat)
  └── projectTasks: ProjectTask[]
       └── timesheets: Timesheet[]
            └── contributor: Contributor (cjm, tjm) -- direct
                 └── employmentPeriods: EmploymentPeriod[] (cjm, tjm) -- via période
```

### 1.2 `Timesheet` (`src/Entity/Timesheet.php`)

| Champ | Type | Nullable | Notes |
|---|---|---|---|
| `id` | int | non | PK auto-increment |
| `company` | Company | non | FK Company (multi-tenant) |
| `contributor` | Contributor | non | Qui a fait les heures |
| `project` | Project | non | Projet rattaché |
| `task` | ProjectTask | **oui** | Tâche optionnelle (peut être null si timesheet libre) |
| `subTask` | ProjectSubTask | oui | Sous-tâche optionnelle |
| `date` | Date | non | Jour des heures |
| `hours` | decimal(5,2) | non | Heures travaillées (ex `7.50`) |
| `notes` | text | oui | Commentaire libre |

**Risk Q1** : `task` nullable → impossible d'attribuer coût/CA à une tâche
spécifique pour ces timesheets. Calcul marge tâche-niveau imprécis.

### 1.3 `ProjectTask` (`src/Entity/ProjectTask.php`)

Champs critiques pour calcul marge :

| Champ | Type | Notes |
|---|---|---|
| `estimatedHoursSold` | int? | Heures vendues (input devis) |
| `estimatedHoursRevisedInternal` | int? | Heures révisées équipe |
| `dailyRate` | decimal? | TJM applicable tâche (override Contributor.tjm) |

Méthodes de calcul (lignes 360-420) :
- `getRemainingEstimatedHours` : revisé OR sold - spent
- `getEstimatedAmountSold` : `(estimatedHoursSold / 8) × dailyRate`
- `getActualCost` : itère timesheets → `hours × cjm/8`

**Risk Q2** : 3 sources de rates possibles (Contributor.cjm, EmploymentPeriod.cjm
via période, ProjectTask.dailyRate override) → ordre de priorité non
explicite dans le code. Magic getter `getRelevantEmploymentPeriod()`.

### 1.4 `Contributor` (`src/Entity/Contributor.php` lines 145-156)

```php
public ?string $cjm = null {
    get => $this->getRelevantEmploymentPeriod()?->cjm ?? $this->cjm;
    ...
}

public ?string $tjm = null {
    get => $this->getRelevantEmploymentPeriod()?->tjm ?? $this->tjm;
    ...
}
```

**Property hook** : `$contributor->cjm` retourne d'abord `EmploymentPeriod.cjm`
résolu sur la date courante, fallback sur `Contributor.cjm` direct.

**Risk Q3** : double nullable :
- `Contributor.cjm` : nullable → contributor sans CJM = coût 0
- `EmploymentPeriod.cjm` : nullable → période sans CJM = fallback
- Si les deux null → coût 0 silencieusement, marge faussée
- Pas de validation côté création contributor

### 1.5 `EmploymentPeriod` (`src/Entity/EmploymentPeriod.php`)

Périodes contractuelles avec rates historisés :
- `startDate` / `endDate`
- `cjm` (coût journalier) — décimal
- `tjm` (taux journalier moyen vendu) — décimal
- `contributor` FK

`getRelevantEmploymentPeriod()` côté Contributor sélectionne période active à
date courante (pas date du timesheet — nuance importante : si timesheet
historique, peut résoudre période actuelle au lieu de période d'origine).

**Risk Q4** : résolution rate non figée à la date du timesheet. Si CJM change
entre temps, recalcul marge passé peut diverger du calcul à l'époque.

---

## 2. Risks data quality identifiés

| ID | Risk | Sévérité | Mitigation US-097 |
|---|---|---|---|
| Q1 | `Timesheet.task` nullable → calcul tâche-niveau imprécis | Moyenne | DDD `WorkItem` exige `taskId` non-null (DRAFT timesheet sans task = exclu de la marge calculation) |
| Q2 | 3 sources de rates non documentées | Élevée | DDD VO `HourlyRate` résolu UNE fois à création WorkItem + figé dans aggregate |
| Q3 | `cjm` / `tjm` doubles nullable → coût 0 silencieux | **Critique** | DDD `WorkItem::create()` throw si rate null. Audit script identifie contributors sans CJM avant migration. |
| Q4 | Rate non figé à la date du timesheet | Élevée | DDD `WorkItem` capture `costRate` + `billedRate` snapshot à création (immutable) |
| Q5 | Pas de validation `hours` > 24h/jour ni `hours` négatives en DB | Moyenne | DDD VO `WorkedHours::fromDecimal()` valide `0 < h <= 24` |
| Q6 | Pas de unique constraint `(contributor, date, task)` → doublons possibles | Faible | DDD : repository `findExistingForDay()` + check applicatif côté UC |
| Q7 | `ProjectTask.dailyRate` override existe mais usage incohérent | Faible | DDD : `WorkItem.billedRate` = task.dailyRate ?? contributor.tjm ?? throw |

---

## 3. Mapping DDD `WorkItem` aggregate cible (informe US-097)

### 3.1 Aggregate `WorkItem`

```
WorkItem {
    WorkItemId id              -- VO UUID-like (legacy bridge fromLegacyInt sur Timesheet.id)
    ProjectId projectId        -- non-null
    ContributorId contributorId -- non-null
    ?ProjectTaskId taskId      -- nullable (cf Risk Q1) MAIS si null, exclu marge calc
    Date workedOn              -- date du travail
    WorkedHours hours          -- VO 0 < h <= 24
    HourlyRate costRate        -- VO non-null FIGÉ snapshot (cjm/8 résolu à création)
    HourlyRate billedRate      -- VO non-null FIGÉ snapshot (tjm/8 résolu à création)
    ?string notes
    DateTimeImmutable createdAt
    ?DateTimeImmutable updatedAt
}
```

### 3.2 Calcul marge

```
WorkItem::cost() = hours × costRate
WorkItem::revenue() = hours × billedRate
WorkItem::margin() = revenue - cost  -- Money VO
WorkItem::marginPercent() = margin / revenue × 100  -- 0 si revenue=0
```

### 3.3 ValueObjects nouveaux

- `WorkItemId` : pattern legacy bridge (cf ContributorId, ProjectId existants)
- `HourlyRate` : Money/8 — non-null par construction
- `WorkedHours` : float 0 < h <= 24, validation constructeur

### 3.4 Repository interface

```
WorkItemRepositoryInterface {
    findById(WorkItemId): WorkItem
    findByProject(ProjectId): WorkItem[]
    findByContributor(ContributorId, DateRange): WorkItem[]
    findByProjectTask(ProjectTaskId): WorkItem[]
    save(WorkItem): void
}
```

### 3.5 Domain Events

- `WorkItemRecordedEvent` (Phase 2 ACL : crée Timesheet flat + DDD wrapper)
- `WorkItemRevisedEvent` (Phase 2 : update hours/notes)
- Note : pas de `MarginThresholdExceededEvent` en Phase 1 (sprint-022 Phase 3)

---

## 4. Recommandations US-097 (sprint-019 Phase 1)

### 4.1 Scope Phase 1 strict

US-097 livrera UNIQUEMENT (3 pts) :
- ✅ DDD `WorkItem` entity + factory `create()` + `reconstitute()` + getters
- ✅ ValueObjects : `WorkItemId`, `HourlyRate`, `WorkedHours`
- ✅ `WorkItemRepositoryInterface` (Domain)
- ✅ `WorkItemRecordedEvent`
- ✅ Tests Unit Domain (entity + VOs + events)
- ❌ Pas d'ACL Phase 2 (translators flat↔DDD = sprint-021)
- ❌ Pas d'UC Application
- ❌ Pas de `MarginCalculator` Domain Service (sprint-022)

### 4.2 Pré-requis avant Phase 2 (sprint-021)

1. **Script audit Contributors sans CJM** (à exécuter prod via cron job temporaire) :
   ```sql
   SELECT c.id, c.email
   FROM contributor c
   LEFT JOIN employment_period ep ON ep.contributor_id = c.id
   WHERE c.cjm IS NULL AND ep.cjm IS NULL;
   ```
   → Output liste contributors → corriger côté admin avant migration DDD.

2. **Décision PO** : timesheets avec `task = NULL` → exclus de la marge OU
   marge calculée niveau projet (allocation fictive) ?

3. **Décision PO** : doublons potentiels `(contributor, date, task)` → dédup
   existant ou tolérer ?

### 4.3 Risk acceptés Phase 1

- Pas d'usage prod immédiat (Phase 2 ACL fournira translator côté
  `DoctrineWorkItemRepository`)
- Tests Unit Domain only (Integration sprint-021 avec real DB)

---

## 5. Conclusion

EPIC-003 démarre sur fondations existantes Timesheet + Contributor.cjm
suffisamment riches MAIS avec 4 risks critiques (Q3 nullable rates, Q4 rate
non figé) à mitiger via DDD VOs immuables.

**US-097 sprint-019** = Phase 1 pure Domain (3 pts). Pas d'écriture flat,
pas de migration data. Audit script Q3 exécution = sprint-020 J1 OPS.

**Prochaines stories EPIC-003 visibles** :
- Sprint-020 : OPS audit Contributor sans CJM + correction admin + DDD Phase 2
  ACL translators
- Sprint-021 : DDD Phase 3 controller + UC `RecordWorkItem`
- Sprint-022 : `MarginCalculator` Domain Service + `MarginThresholdExceededEvent`
- Sprint-023 : Dashboard 3 KPIs étendus (DSO + temps facturation + adoption)

---

**Date** : 2026-05-08
**Auteur** : Tech Lead
**Status** : ✅ Audit livré, informe US-097
