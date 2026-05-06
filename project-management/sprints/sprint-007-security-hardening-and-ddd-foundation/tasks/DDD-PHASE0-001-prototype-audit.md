# DDD-PHASE0-001 — Audit branche prototype Clean Architecture

**Sprint**: 007 — Security Hardening + DDD Foundation
**Story**: DDD-PHASE0-001 (2 pts, doc-only)
**EPIC**: EPIC-001 — Migration Clean Architecture + DDD
**Date audit**: 2026-05-06
**Auteur**: Claude Opus 4.7 (1M context)
**Branche auditée**: `feature/sprint-001-clean-architecture-structure`

---

## TL;DR

Branche prototype contient **scaffolding DDD utilisable** (10 BCs + Shared kernel + Doctrine custom types) MAIS provoque **régression massive** si mergée telle quelle.

**Décision**: ❌ NE PAS rebase/merge entière. ✅ Cherry-pick **fichier par fichier**, en commençant par Shared kernel (story DDD-PHASE0-002).

---

## 1. Métadonnées branche

| Attribut | Valeur |
|---|---|
| Nom | `feature/sprint-001-clean-architecture-structure` |
| Dernier commit | `21039f6` |
| Date | 2026-01-19 (4 mois) |
| Auteur | Claude Opus 4.5 |
| Commits uniques vs `main` | 2 |
| Fichiers modifiés vs `main` | **2245** |
| Ajouts | +499 517 lignes |
| Suppressions | -53 878 lignes |
| Fichiers ajoutés | 1 517 |
| Fichiers supprimés | **248** ⚠️ |

---

## 2. Inventaire scaffolding DDD ajouté

### 2.1 Bounded Contexts créés (10)

```
src/Domain/
├── BusinessUnit/         (Entity + 3 Events + 2 Exceptions + Repo + ValueObject)
├── Client/               (Entity + 1 Event + 1 Exception + Repo + 3 ValueObjects)
├── Company/              (Entity + 3 Events + 3 Exceptions + Repo + 5 ValueObjects)
├── Contributor/          (Entity + 3 Events + 1 Exception + Repo + 2 ValueObjects)
├── Invoice/              (2 Entities + 4 Events + 2 Exceptions + Repo + 5 ValueObjects)
├── Order/                (3 Entities + Events + Exceptions + Repo + ValueObjects)
├── Project/              (Entity + 2 Events + 2 Exceptions + Repo + 3 ValueObjects)
├── Shared/               (DomainException + 2 Interfaces + 1 Trait + 2 ValueObjects)
├── Timesheet/            (Entity + 3 Events + 2 Exceptions + Repo + 2 ValueObjects)
└── User/                 (Entity + 4 Events + 3 Exceptions + Repo + 3 ValueObjects)
```

### 2.2 Shared kernel (6 fichiers)

```
src/Domain/Shared/
├── Exception/DomainException.php           (11 lignes)
├── Interface/AggregateRootInterface.php    (13 lignes)
├── Interface/DomainEventInterface.php      (12 lignes)
├── Trait/RecordsDomainEvents.php           (29 lignes)
├── ValueObject/Email.php                   (43 lignes)
└── ValueObject/Money.php                   (147 lignes)
```

→ Total **255 lignes**, zéro dépendance externe au repo, **0 collision** avec `src/` actuel.

### 2.3 Infrastructure additive

```
src/Infrastructure/Persistence/Doctrine/
├── Mapping/
│   ├── Client/Client.orm.xml
│   ├── Money.orm.xml
│   ├── Order.orm.xml
│   ├── OrderLine.orm.xml
│   └── OrderSection.orm.xml
└── Type/
    ├── AbstractEnumType.php
    ├── AbstractStringType.php
    ├── AbstractUuidType.php
    ├── ClientIdType.php
    ├── CompanyNameType.php
    ├── ContractTypeType.php
    ├── EmailType.php
    ├── OrderIdType.php
    ├── OrderLineIdType.php
    ├── OrderLineTypeType.php
    ├── OrderSectionIdType.php
    ├── OrderStatusType.php
    └── ServiceLevelType.php
```

→ **3 abstract types réutilisables** + 10 types concrets. Pattern factorisé clean.

---

## 3. Conflits identifiés (RISQUE RÉGRESSION)

### 3.1 ⛔ BLOQUANT: Vacation BC supprimé

Prototype **supprime** la totalité du BC Vacation qui est aujourd'hui **LIVE en production** (mergé via sprint-006 + couvert par VacationVoter sprint-007).

```
D  src/Domain/Vacation/Entity/Vacation.php
D  src/Domain/Vacation/Event/VacationApproved.php
D  src/Domain/Vacation/Event/VacationCancelled.php
D  src/Domain/Vacation/Event/VacationRejected.php
D  src/Domain/Vacation/Event/VacationRequested.php
D  src/Domain/Vacation/Exception/InvalidStatusTransitionException.php
D  src/Domain/Vacation/Exception/InvalidVacationException.php
D  src/Domain/Vacation/Exception/VacationNotFoundException.php
D  src/Domain/Vacation/Repository/VacationRepositoryInterface.php
D  src/Domain/Vacation/ValueObject/DailyHours.php
D  src/Domain/Vacation/ValueObject/DateRange.php
D  src/Domain/Vacation/ValueObject/VacationStatus.php
D  src/Domain/Vacation/ValueObject/VacationType.php
D  src/Application/Vacation/**/*.php   (16 fichiers Command + Query + DTO + Notification)
D  src/Infrastructure/.../DoctrineVacationRepository.php
D  src/Infrastructure/.../Mapping/Entity/Vacation.orm.xml
D  src/Presentation/Vacation/Controller/VacationApprovalController.php
D  src/Presentation/Vacation/Controller/VacationRequestController.php
D  src/Presentation/Vacation/Form/VacationRequestType.php
```

→ Merger = **perdre fonctionnalité métier en prod** (demande/approbation congés). Inacceptable.

### 3.2 ⚠️ Régressions services applicatifs

```
D  src/Command/BackupDumpCommand.php           (253 lignes)
D  src/Command/BackupRestoreCommand.php        (260 lignes)
D  src/Command/ImportTheTribeNotionCommand.php (678 lignes)
D  src/Command/ImportTheTribePlanningCommand.php (529 lignes)
... + 244 autres deletions
```

→ Audit ligne par ligne nécessaire pour identifier celles qui sont legacy (ok à perdre) vs prod (à conserver).

### 3.3 ⚠️ Modifications massives sur services existants

Exemple `src/Command/CreateTestDataCommand.php`: **304 → 175 lignes** (-129 lignes). Refactor risqué sans contexte.

### 3.4 Conflits potentiels dans `tests/`

Plusieurs tests modifiés en parallèle (cf. ProjectRiskAnalyzerTest, ForecastingServiceTest, etc.) — divergence avec stabilisations sprint-005/006.

---

## 4. Stratégie cherry-pick recommandée

### Phase 0 (sprint-007) — Foundation safe

| Story | Pts | Contenu | Risque |
|---|---:|---|---|
| **DDD-PHASE0-002** (à venir) | 3 | Shared kernel (6 fichiers) + 3 Abstract Doctrine Types | 🟢 None — additif |

### Phase 1 (sprint-008+) — BCs additifs

Cherry-pick par BC, **PAS de suppressions, PAS de Vacation**:

1. **Project BC** (5 fichiers Domain) — BC stub présent dans main vide → migration douce
2. **Client BC** (5 fichiers Domain) — gap-analysis a flagué stub vide
3. **Order BC** (Domain seulement, pas Infrastructure) — modèle commercial DDD
4. **Invoice BC** (Domain) — modèle facturation DDD
5. **Contributor BC** — conflit avec Entity flat existante, **étude impact requise**
6. **Company BC** — conflit avec Entity flat existante (multi-tenant), **étude impact requise**
7. **Timesheet BC** — modèle DDD additif
8. **User BC** — conflit majeur avec User flat (Security FOSUserBundle-style), **dernier**
9. **BusinessUnit BC** — additif, low priority

### Phase 2 (sprint-009+) — Migration progressive

- Bridge entre Entity flat (`src/Entity/*`) et Domain DDD (`src/Domain/*`)
- Pattern strangler fig: nouveau code écrit en DDD, ancien code migré progressivement
- **Vacation reste tel quel** (déjà DDD partiel) — pas de duplication

### Phase 3 (sprint-010+) — Décommissionnement

- Suppression Entity flat une fois zéro référence
- Vérification Deptrac
- Migration finale Doctrine annotations → XML

---

## 5. Fichiers à NE PAS prendre du prototype

❌ **Toutes les deletions (248 fichiers)**
❌ **Modifications de fichiers existants** (services, commands, tests) — divergent trop
❌ **Vacation BC du prototype** — version main est plus complète et live
❌ `src/Application/*` complet du prototype — orchestrations divergent

---

## 6. Fichiers prioritaires (DDD-PHASE0-002)

```
✅ src/Domain/Shared/Exception/DomainException.php
✅ src/Domain/Shared/Interface/AggregateRootInterface.php
✅ src/Domain/Shared/Interface/DomainEventInterface.php
✅ src/Domain/Shared/Trait/RecordsDomainEvents.php
✅ src/Domain/Shared/ValueObject/Email.php
✅ src/Domain/Shared/ValueObject/Money.php
✅ src/Infrastructure/Persistence/Doctrine/Type/AbstractEnumType.php
✅ src/Infrastructure/Persistence/Doctrine/Type/AbstractStringType.php
✅ src/Infrastructure/Persistence/Doctrine/Type/AbstractUuidType.php
```

→ **9 fichiers, ~510 lignes**. Risque de régression: **0**.

Validation: `phpunit` reste vert (aucune dépendance), Deptrac reste vert (Domain/Shared n'autorise rien), PHPStan max passe.

---

## 7. Action items

- [x] Audit branche réalisé (cette doc)
- [ ] DDD-PHASE0-002: Cherry-pick Shared kernel + Abstract Doctrine Types (3 pts)
- [ ] Sprint-008 planning: identifier 3 BCs prioritaires (Client + Project + Order recommandés)
- [ ] EPIC-001 update: ajouter section "Stratégie cherry-pick" avec roadmap Phase 0-3
- [ ] Conserver branche prototype en READ-ONLY (référence) jusqu'à fin EPIC-001
- [ ] Tagger commit `21039f6` avec `proto/ddd-baseline-2026-01-19` pour archivage

---

## 8. Décision finale

> **Branche prototype = matériau source, pas merge candidate.**
>
> Stratégie: cherry-pick **fichier par fichier** par ordre de risque croissant. Shared kernel d'abord (DDD-PHASE0-002), puis BCs additifs (sprint-008+), puis remplacement progressif Entity flat → Domain DDD via pattern strangler fig.

**Approuvé**: `chore/ddd-phase0-001-prototype-audit` → audit doc only.

---

**Next**: `/project:run-story DDD-PHASE0-002` (cherry-pick Shared kernel, 3 pts).
