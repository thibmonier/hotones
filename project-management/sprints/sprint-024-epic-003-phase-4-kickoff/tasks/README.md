# Tâches - Sprint 024

## Vue d'ensemble

| US                | Titre                                | Pts | Tâches | Heures | Statut |
|-------------------|--------------------------------------|----:|-------:|-------:|--------|
| US-110            | KPI DSO (Days Sales Outstanding)     |   3 |      6 |   11   | 🔲     |
| US-111            | KPI temps facturation                |   3 |      6 |   11   | 🔲     |
| US-112            | KPI adoption marge temps réel        |   2 |      5 |    7   | 🔲     |
| US-113            | Migration WorkItem.cost legacy → DDD |   3 |      7 |   15   | 🔲     |
| OPS-PRE5-DECISION | ADR Render redeploy ou Out Backlog   |   2 |      5 |  3.5   | 🔲     |
| **Total**         |                                      | **13** | **29** | **47.5** | |

> ⚠️ Engagement provisoire 13 pts > 12 ferme. Planning P1 tranche scope final.

## Répartition par type

| Type     | Tâches | Heures | %      |
|----------|-------:|-------:|-------:|
| [BE]     |     12 |   26   | 55 %   |
| [TEST]   |      7 |   13   | 27 %   |
| [DB]     |      1 |    1   |  2 %   |
| [FE-WEB] |      4 |    6.5 | 14 %   |
| [DOC]    |      2 |    2   |  4 %   |
| [OPS]    |      3 |   1.5  |  3 %   |
| **Total**|   **29** |**47.5h**| 100 %  |

## Stack technique

| Couche | Technologie | Tâches |
|--------|-------------|-------:|
| Domain pure (Domain Services KPI) | PHP 8.5, DDD pattern | 4 |
| Persistence | Doctrine ORM + Doctrine migration | 1 DB + 3 BE |
| Cache | Redis (1h TTL) | inclus BE |
| API | API Platform (réutilise dashboard EPIC-002) | 0 (dashboard existant) |
| Frontend Web | Twig + Stimulus (widgets dashboard) | 4 |
| Tests | PHPUnit Unit + Integration | 7 |
| OPS | Symfony Console commande migration | 3 |

## Fichiers

- [US-110 - KPI DSO](./US-110-tasks.md)
- [US-111 - KPI temps facturation](./US-111-tasks.md)
- [US-112 - KPI adoption marge](./US-112-tasks.md)
- [US-113 - Migration WorkItem.cost legacy](./US-113-tasks.md)
- [OPS-PRE5 - Décision Render redeploy](./OPS-PRE5-tasks.md)
- [Task Board (kanban)](../task-board.md)

## Conventions

- **ID** : T-[US]-[Numéro] (ex: T-110-01)
- **Taille** : 0.5h - 8h max
- **Statuts** : 🔲 To Do | 🔄 In Progress | 👀 Review | ✅ Done | 🚫 Blocked
- **Max 2 tâches** en cours par personne
- **Dépendances explicites** (Mermaid si > 5)

## Patterns réutilisés

| Pattern | Source | Réutilisation sprint-024 |
|---|---|---|
| Domain Service KPI calculator | sprint-022 MarginCalculator | US-110/111/112 |
| Persistence snapshot event-driven | sprint-023 US-107 | US-110/111 |
| Cache Redis 1h TTL | sprint-017 dashboard | US-110/111 |
| Configurabilité hiérarchique seuils | sprint-023 US-108 | US-110/111 |
| Migration Symfony Console + dry-run | sprint-013 EPIC-001 Phase 4 | US-113 |
| ADR Out Backlog avec triggers replan | sprint-022 ADR-0017 | OPS-PRE5-DECISION |
