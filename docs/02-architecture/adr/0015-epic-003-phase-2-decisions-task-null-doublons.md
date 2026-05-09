# ADR-0015 — EPIC-003 Phase 2 : décisions task=NULL + doublons + invariant journalier

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-09 |
| Sprint | sprint-020 sub-epic A ATELIER-PHASE-2 |
| Story | sprint-019 retro A-3 héritage |
| Auteur | Tech Lead + PO (atelier sprint-020 J1) |

---

## Contexte

Audit data EPIC-003 sprint-019 a identifié 2 questions ouvertes nécessitant
arbitrage PO avant US-098 Phase 2 ACL design figé :

| # | Question | Source |
|---|---|---|
| Q1 | Timesheets `task = NULL` → exclus marge OU allocation fictive niveau projet ? | Risk Q1 audit |
| Q2 | Doublons `(contributor, date, task)` → dédup OU toléré ? | Risk Q6 audit |

Atelier PO sprint-020 J1 a tranché.

---

## Décisions PO

### Q1 — Allocation fictive niveau projet (option B)

**Décision** : Timesheets `task = NULL` créent un `WorkItem` rattaché au
projet sans tâche (`taskId = null`). Coût + CA agrégés au niveau projet
sans drill-down par tâche.

**Justification PO** :
> « Des tâches non prévues font souvent leur apparition et il est nécessaire
> de les ré-arranger a posteriori. »

Pattern réel agence : timesheets saisis sur projet AVANT décomposition
fine en tâches. Forcer `task` non-null casserait workflow équipe.

### Q2 — Toléré sans constraint (option B)

**Décision** : Pas de unique constraint DB sur `(contributor_id, date,
task_id)`. Doublons légitimes acceptés.

**Justification PO** :
> « Il est possible de saisir 1h le matin et 1h l'après-midi. La sécurité
> provient du temps quotidien (somme des temps du jour) qui doit rester sous
> le temps de travail défini pour le contributeur. »

→ Nouvel invariant **journalier** émerge :
- `sum(timesheet.hours WHERE contributor=X AND date=Y) <= dailyHoursContributor`
- `dailyHoursContributor` dérivé de `EmploymentPeriod.weeklyHours ×
  workTimePercentage / 100 / 5` (jours ouvrés).

---

## Conséquences design DDD WorkItem

### Aggregate `WorkItem` (Phase 1 livré sprint-019 US-097)

Ne change PAS Phase 1. `WorkItem` a déjà `?taskId` nullable côté champs
supportés (sera ajouté Phase 2 ACL — `WorkItem` Phase 1 sans taskId pour
simplicité).

**Action sprint-020** : étendre `WorkItem` avec `?ProjectTaskId taskId`
nullable champ + getter + factory `create()`.

### UC `RecordWorkItem` (sprint-021 Phase 3)

Validation invariant journalier déléguée au UC (pas à l'Aggregate seul —
nécessite query repo pour les autres WorkItems même jour).

```
RecordWorkItem::execute(command):
    1. Charge tous WorkItems existants (contributorId, date) via repo.findByContributorAndDate
    2. Calcule dailyTotal = sum(hours) + command.hours
    3. Charge dailyMaxHours = EmploymentPeriod.dailyHours(contributorId, date)
    4. Si dailyTotal > dailyMaxHours → throw DailyHoursExceededException
    5. Sinon → WorkItem::create() + repo.save() + dispatch event
```

### Repository `WorkItemRepositoryInterface` (Phase 1 livré)

Ajouter méthode `findByContributorAndDate(ContributorId, DateTimeImmutable):
WorkItem[]` Phase 2 sprint-020.

### Domain Service `DailyHoursValidator` (sprint-021 Phase 3)

Encapsule le calcul `dailyMaxHours` depuis `EmploymentPeriod` :

```
DailyHoursValidator::dailyMaxHours(ContributorId, DateTimeImmutable): WorkedHours
```

Dépendance lecture `EmploymentPeriodRepository` — interface Domain ajoutée
Phase 3 (ou via existing flat repository wrapped en ACL adapter).

---

## Trigger réversibilité

Reconsidérer Q1 (allocation fictive) si :
1. Adoption taux `task != null` < 30 % à 3 mois prod (signal saisie reste
   dégradée)
2. Drill-down marge par tâche demandé par PO en force (vs approximation
   niveau projet)

Reconsidérer Q2 (toléré) si :
1. Volumétrie doublons saisie accidentelle > 5 % timesheets prod (signal
   bug UI)
2. Plaintes utilisateurs « j'ai saisi 2x sans m'en rendre compte »
   récurrentes
3. `DailyHoursExceededException` triggered > 10x / sem prod (signal seuil
   journalier saturé / incorrect)

---

## Conséquences

### Positives
- ✅ Workflow équipe préservé (saisie projet sans tâche AVANT décomposition)
- ✅ Saisie matin/aprem fractionnée légitime (pas de constraint bloquante)
- ✅ Sécurité via invariant journalier explicite + validable (Domain Service)
- ✅ Pas de migration data risquée (pas de unique constraint à ajouter)

### Négatives
- ❌ Pas de drill-down marge par tâche pour timesheets `task = NULL`
- ❌ Invariant journalier requiert query repo dans UC (latence + coupling
  Phase 3)
- ❌ Si dailyHours mal configuré (NULL ou aberrant), invariant inopérant —
  audit `app:audit:contributors-cjm` à étendre `--audit-daily-hours` futur

---

## Action items

| ID | Action | Owner | Sprint |
|---|---|---|---|
| A-1 | Étendre `WorkItem` Phase 1 avec `?ProjectTaskId taskId` champ + getter + factory | Tech Lead | sprint-020 sub-epic A US-098 |
| A-2 | Ajouter `findByContributorAndDate()` à `WorkItemRepositoryInterface` | Tech Lead | sprint-020 sub-epic A US-098 |
| A-3 | UC `RecordWorkItem` avec validation invariant journalier | Tech Lead | sprint-021 Phase 3 |
| A-4 | Domain Service `DailyHoursValidator` | Tech Lead | sprint-021 Phase 3 |
| A-5 | `DailyHoursExceededException` Domain | Tech Lead | sprint-021 Phase 3 |
| A-6 | Étendre audit script `--audit-daily-hours` (EmploymentPeriod weeklyHours/workTimePercentage manquants) | Tech Lead | sprint-021 sub-epic D si traction |

---

## Alternatives écartées

### Q1 — Option A (exclus marge)
**Écarté** : marge sous-estime coût réel projet (heures réelles non comptées
si task=NULL). Projets paraissent plus rentables qu'ils ne sont → décision
business faussée.

### Q1 — Option C (force task non-null UI)
**Écarté** : workflow équipe casse. Saisir tâches AVANT savoir lesquelles
existent = friction. Migration historique data coûteuse.

### Q2 — Option A (dédup hard + unique constraint)
**Écarté** : casse le workflow saisie matin/aprem fractionné légitime.
Migration data risquée (fusion automatique = perte info).

### Q2 — Option C (alerte sans bloquer)
**Écarté** : alerte sur doublon légitime = bruit. Sécurité via invariant
journalier suffit.

---

## Liens

- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- Audit data : `docs/02-architecture/epic-003-audit-existing-data.md`
- Audit Contributors CJM : `docs/02-architecture/epic-003-audit-contributors-cjm-runbook.md`
- Sprint-019 retro A-3 héritage
- Sprint-020 sub-epic A US-098

---

**Date de dernière mise à jour :** 2026-05-09
**Version :** 1.0.0
