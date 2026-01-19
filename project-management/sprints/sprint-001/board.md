# Sprint 001 - Kanban Board

> **Dernière mise à jour:** 2026-01-18
> **Sprint:** Foundation Architecture

---

## Tableau Kanban

### US-001 - Structure Domain initiale (3 pts) 🟢 Done

| Task | Type | Statut | Notes |
|------|------|--------|-------|
| Création structure `src/Domain/` | [BE] | 🟢 Done | 9 bounded contexts |
| Création structure `src/Application/` | [BE] | 🟢 Done | UseCase, Query, EventHandler |
| Création structure `src/Infrastructure/` | [BE] | 🟢 Done | Persistence, Notification, Cache |
| Création structure `src/Presentation/` | [BE] | 🟢 Done | Controller (Web, Api, Admin) |
| Configuration Deptrac | [OPS] | 🟢 Done | deptrac.yaml validé |
| Documentation architecture | [DOC] | 🟢 Done | docs/02-architecture/ |

### US-002 - Extraction entité Client (8 pts) 🟢 Done

| Task | Type | Statut | Notes |
|------|------|--------|-------|
| Entité Domain `Client` pure | [BE] | 🟢 Done | Sans annotations Doctrine |
| Value Object `ClientId` | [BE] | 🟢 Done | UUID v4 typé |
| Value Object `CompanyName` | [BE] | 🟢 Done | Validation intégrée |
| Value Object `ServiceLevel` | [BE] | 🟢 Done | Enum (Standard, Premium, Enterprise) |
| Value Object `Email` | [BE] | 🟢 Done | Shared kernel, validation RFC |
| Domain Event `ClientCreatedEvent` | [BE] | 🟢 Done | Avec RecordsDomainEvents trait |
| Trait `RecordsDomainEvents` | [BE] | 🟢 Done | Gestion événements domaine |
| Factory method `create()` | [BE] | 🟢 Done | Constructor private |
| Method `updateContactInfo` | [BE] | 🟢 Done | Email + phone + address |
| Method `updateServiceLevel` | [BE] | 🟢 Done | Avec événement |
| Method `rename` | [BE] | 🟢 Done | CompanyName VO |
| Method `activate/deactivate` | [BE] | 🟢 Done | Gestion statut |
| Tests unitaires PHPUnit | [TEST] | 🟢 Done | 460 lignes, Given/When/Then |
| Mapping Doctrine XML | [BE] | ⏸️ Blocked | Reporté (quotas) |
| Types Doctrine custom | [BE] | ⏸️ Blocked | Reporté (quotas) |

---

## Résumé par Statut

| Statut | Count | % |
|--------|-------|---|
| 🟢 Done | 19 | 90% |
| 🟡 In Progress | 0 | 0% |
| ⏸️ Blocked | 2 | 10% |
| 🔴 To Do | 0 | 0% |

---

## Blocages Actifs

### ⏸️ Mapping Doctrine XML
- **Raison:** Quotas API atteints
- **Impact:** Infrastructure non complète
- **Solution:** Scheduler nocturne ou sprint suivant

### ⏸️ Types Doctrine custom
- **Raison:** Dépend du mapping XML
- **Impact:** Persistence non fonctionnelle
- **Solution:** Traiter avec le mapping

---

## Légende

| Type | Description |
|------|-------------|
| [BE] | Backend (PHP/Symfony) |
| [FE] | Frontend (Twig/JS) |
| [DB] | Database/Migration |
| [TEST] | Tests |
| [DOC] | Documentation |
| [OPS] | DevOps/CI |
| [REV] | Code Review |

| Statut | Description |
|--------|-------------|
| 🔴 To Do | Pas commencé |
| 🟡 In Progress | En cours |
| ⏸️ Blocked | Bloqué |
| 🟢 Done | Terminé |
