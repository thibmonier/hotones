# Task Board — Sprint 024

## Légende

| Icône | Statut       | Description                           |
|-------|--------------|---------------------------------------|
| 🔲    | To Do        | Pas encore commencé                   |
| 🔄    | In Progress  | En cours                              |
| 👀    | Review       | PR ouverte, en attente review         |
| ✅    | Done         | Mergé sur main, DoD validée           |
| 🚫    | Blocked      | Bloqué par obstacle                   |
| ⏳    | Pending      | Conditionnel (option A/B/C non choisie) |

## 🔲 À Faire (29 tâches)

### US-110 KPI DSO (6 tâches, 11h)

| ID | Tâche | Type | Estim | Dépend |
|----|-------|------|------:|--------|
| T-110-01 | Domain Service `DsoCalculator` + tests Unit | [BE]   | 3h | — |
| T-110-02 | Repository query 30/90/365 rolling | [BE]   | 2h | T-110-01 |
| T-110-03 | Subscriber `InvoicePaidEvent` invalidation | [BE]   | 1h | T-110-02 |
| T-110-04 | Widget Twig + Stimulus | [FE-WEB] | 2h | T-110-02 |
| T-110-05 | Alerte Slack seuil rouge | [BE]   | 1h | T-110-02 |
| T-110-06 | Tests Integration query + cache | [TEST] | 2h | T-110-03 |

### US-111 KPI temps facturation (6 tâches, 11h)

| ID | Tâche | Type | Estim | Dépend |
|----|-------|------|------:|--------|
| T-111-01 | Domain Service `BillingLeadTimeCalculator` + tests Unit | [BE]   | 3h | — |
| T-111-02 | Repository query percentiles + top 3 | [BE]   | 2h | T-111-01 |
| T-111-03 | Subscriber `InvoiceEmittedEvent` invalidation | [BE]   | 1h | T-111-02 |
| T-111-04 | Widget Twig + top 3 clients | [FE-WEB] | 2h | T-111-02 |
| T-111-05 | Alerte Slack médiane 30j | [BE]   | 1h | T-111-02 |
| T-111-06 | Tests Integration percentiles | [TEST] | 2h | T-111-03 |

### US-112 KPI adoption marge (5 tâches, 7h)

| ID | Tâche | Type | Estim | Dépend |
|----|-------|------|------:|--------|
| T-112-01 | Domain Service `MarginAdoptionCalculator` + tests Unit | [BE]   | 2h | — |
| T-112-02 | Repository query CASE WHEN | [BE]   | 1h | T-112-01 |
| T-112-03 | Widget Twig + bar chart | [FE-WEB] | 1.5h | T-112-02 |
| T-112-04 | Alerte Slack persistante 7j | [BE]   | 1.5h | T-112-02 |
| T-112-05 | Tests Integration CASE WHEN | [TEST] | 1h | T-112-02 |

### US-113 Migration WorkItem.cost legacy (7 tâches, 12h sprint + 0.5h hors sprint)

| ID | Tâche | Type | Estim | Dépend |
|----|-------|------|------:|--------|
| T-113-01 | Migration Doctrine cols (`migrated_at` + `legacy_cost_drift` + `legacy_cost_cents`) | [DB]   | 1h | — |
| T-113-02 | Domain Service `WorkItemMigrator` + tests Unit | [BE]   | 4h | T-113-01 |
| T-113-03 | Commande Symfony `app:workitem:migrate-legacy-cost` | [BE]   | 2h | T-113-02 |
| T-113-04 | Export CSV drift report | [BE]   | 1h | T-113-02 |
| T-113-05 | Tests Integration up/down idempotente | [TEST] | 3h | T-113-03 |
| T-113-06 | Runbook prod migration | [DOC]  | 1h | T-113-03 |
| T-113-07 | Dry-run prod (hors sprint user-tracked) | [OPS]  | 0.5h | T-113-04 |

### OPS-PRE5-DECISION (5 tâches, 3.5h max)

| ID | Tâche | Type | Estim | Dépend |
|----|-------|------|------:|--------|
| T-PRE5-01 | Atelier OPS-PREP-J0 J-2 matrix décision | [DOC] | 0.5h | — |
| T-PRE5-02 | Rédaction ADR-0018 | [DOC] | 1h | T-PRE5-01 |
| T-PRE5-03 | Option A : redeploy Render (CONDITIONAL) | [OPS] | 0.25h | T-PRE5-02 |
| T-PRE5-04 | Option B : fermeture holdover (CONDITIONAL) | [OPS] | 0.5h | T-PRE5-02 |
| T-PRE5-05 | Option C : création story OPS (CONDITIONAL) | [OPS] | 1h | T-PRE5-02 |

## 🔄 En Cours (0)

| ID | US | Tâche | Démarré | Assigné |
|----|-----|-------|---------|---------|
| — | — | — | — | — |

## 👀 En Review (0)

| ID | US | Tâche | Reviewer |
|----|-----|-------|----------|
| — | — | — | — |

## ✅ Terminé (0)

| ID | US | Tâche | Réel | Terminé |
|----|-----|-------|------|---------|
| — | — | — | — | — |

## 🚫 Bloqué (0)

| ID | US | Raison | Action |
|----|-----|--------|--------|
| — | — | — | — |

## Métriques

- **Tâches totales** : 29 (5 conditionnelles US-113-07 + OPS-PRE5)
- **Tâches terminées** : 0 (0 %)
- **Heures estimées** : 47.5h
- **Heures consommées** : 0h
- **Heures restantes** : 47.5h
- **Vélocité cible** : 12 pts ferme + 1-2 pts libre
- **Engagement provisoire** : 13 pts (à trancher Planning P1)

## Capacity check

```
12 pts ferme × ~4h/pt = 48h capacité ferme
+ 1-2 pts libre × ~4h/pt = 4-8h capacité libre
= 52-56h capacité totale

Engagement actuel = 47.5h → tient dans capacité ferme ✅
Cap libre Mago batch (1.5-2 pts) ajouterait 6-8h
```

## Notes Sprint Planning P1 (à remplir 2026-05-27)

- Sprint Goal final définitif
- Scope tranché (Option A/B/C cap libre)
- Stories engagées (vs reportées)
- Assignations équipe (TBD)
- Risques discutés
