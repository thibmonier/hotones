# Atelier PO EPIC-003 Phase 3 — Prep

| Champ | Valeur |
|---|---|
| Sprint cible | 021 (Phase 3 démarrage) |
| Date prévue | Sprint-021 J0 (kickoff matin) |
| Durée | 90 min |
| Participants | PO + Tech Lead |
| Owner doc | Tech Lead |
| Origine | Sprint-020 retro action A-3 |
| Livrable | ADR-0016 décisions Phase 3 + sprint-021 backlog ferme |

---

## 1. Contexte

EPIC-003 Phase 1 (sprint-019) + Phase 2 (sprint-020) livrés :
- Phase 1 : DDD `WorkItem` aggregate + VOs + Events + Repository interface
- Phase 2 : translators flat↔DDD + `DoctrineDddWorkItemRepository` + ADR-0015 (task=NULL + doublons + invariant journalier)

Phase 3 = passage **lecture → écriture** avec mutation domain via UC.

### Scope Phase 3 par ADR-0013

| Composant | Origine ADR | Statut |
|---|---|---|
| UC `RecordWorkItem` | ADR-0015 A-3 | À designer |
| Domain Service `DailyHoursValidator` | ADR-0015 A-4 | À designer |
| `DailyHoursExceededException` Domain | ADR-0015 A-5 | À designer |
| Workflow Symfony state machine `WorkItem` | ADR-0013 stack | À designer |
| UC `CalculateProjectMargin` | ADR-0013 MVP | À designer |
| `MarginThresholdExceededEvent` + alerte Slack | ADR-0013 MVP | Sprint-022 ? |
| Dashboard 3 KPIs (DSO + facturation + adoption) | ADR-0013 KPIs | Sprint-022 ? |

Sprint-021 capacité 12 pts → focus UC `RecordWorkItem` + invariant journalier. UC `CalculateProjectMargin` + alerting + KPIs probable sprint-022.

---

## 2. Pré-lectures (envoyées PO J-1)

- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- ADR-0015 — EPIC-003 Phase 2 décisions task=NULL + doublons + invariant journalier
- Audit data : `docs/02-architecture/epic-003-audit-existing-data.md`
- Audit Contributors CJM runbook : `docs/02-architecture/epic-003-audit-contributors-cjm-runbook.md`

---

## 3. Agenda

| Time | Bloc | Durée |
|---|---|---:|
| 00:00 | Bloc 1 — UC `RecordWorkItem` UI/UX scope | 20 min |
| 00:20 | Bloc 2 — Validation + édition + verrouillage | 15 min |
| 00:35 | Bloc 3 — Workflow state machine `WorkItem` | 10 min |
| 00:45 | Bloc 4 — UC `CalculateProjectMargin` trigger + scope | 15 min |
| 01:00 | Bloc 5 — Seuil alerte marge + EmploymentPeriod data quality | 15 min |
| 01:15 | Bloc 6 — Backlog sprint-021 ferme + capacité | 15 min |
| 01:30 | Fin | |

---

## 4. Questions par bloc

### Bloc 1 — UC `RecordWorkItem` UI/UX scope

#### Q1.1 — Surface saisie ?

| Option | Description | Effort sprint-021 |
|---|---|---:|
| **A** — Twig grille hebdo (jours × projets) | Grille type tableur, drag-drop, total auto | 5 pts |
| **B** — Twig formulaire ligne-par-ligne | Add 1 entrée à la fois, liste paginée | 3 pts |
| **C** — API JSON only (CLI/import) | Pas d'UI Phase 3, intégration tierce | 2 pts |
| **D** — Hybride : A pour saisie hebdo + C pour import bulk | UI + API import | 6 pts |

**Recommandation Tech Lead** : **B** pour MVP sprint-021. Grille hebdo (A) UX coûteuse, à reporter sprint-022+ après mesure adoption B. Import bulk (C/D) si demande PO réelle post-MVP.

**Décision PO** : ☐ A ☐ B ☐ C ☐ D ☐ Autre :

#### Q1.2 — Champs minimum saisie ?

Champs candidats (ADR-0015 + ADR-0013) :

| Champ | Type | Obligatoire MVP ? |
|---|---|:---:|
| `date` | DateImmutable | ✅ |
| `contributorId` | Auto (user connecté) | ✅ |
| `projectId` | Sélection liste | ✅ |
| `taskId` | ?ProjectTaskId nullable (ADR-0015 Q1) | ☐ optionnel |
| `hours` | WorkedHours (decimal 0.25 step) | ✅ |
| `comment` | ?string nullable | ☐ optionnel |
| `tags` | ?string[] (catégorisation) | ❌ post-MVP |

**Recommandation Tech Lead** : 5 champs ✅ + `comment` optionnel. Tags = sprint-022+ si demande.

**Décision PO** : ☐ Liste ci-dessus ☐ Ajout : ☐ Retrait :

#### Q1.3 — Step heures saisie ?

| Option | Step | Note |
|---|---:|---|
| **A** — 0.25h (15 min) | 0.25 | Précis mais friction saisie |
| **B** — 0.5h (30 min) | 0.5 | Compromis usabilité/précision |
| **C** — 1h | 1.0 | Simple mais peu précis |

**Recommandation Tech Lead** : **B** (0.5h). VO `WorkedHours` validation Phase 1 supporte n'importe quel step (precision 2 décimales).

**Décision PO** : ☐ A ☐ B ☐ C

---

### Bloc 2 — Validation + édition + verrouillage

#### Q2.1 — Soumission saisie ?

| Option | Description |
|---|---|
| **A** — Auto-save ligne par ligne | Submit implicite après chaque save |
| **B** — Submit explicite hebdo | Bouton « Soumettre semaine » fin saisie |
| **C** — Hybride : draft auto + submit hebdo | Draft persisté + état `submitted` séparé |

**Recommandation Tech Lead** : **A** sprint-021 MVP simple. **C** intéressant mais nécessite état Workflow Symfony supplémentaire (état `submitted`) — sprint-022+ si traction.

**Décision PO** : ☐ A ☐ B ☐ C

#### Q2.2 — Édition rétroactive autorisée combien de jours en arrière ?

| Option | Limite | Note |
|---|---|---|
| **A** — Sans limite | Tout WorkItem éditable indéfiniment | Risque incohérence facturation |
| **B** — 7 jours | Édition semaine en cours + précédente | Compromis pratique |
| **C** — 30 jours | Édition mois en cours | Plus permissif |
| **D** — Bloqué après facturation projet | Verrouillage post-`Invoice.billed` | Cohérence comptable forte |

**Recommandation Tech Lead** : **B** + **D** combinés (7 jours OU si projet déjà facturé → bloqué). Workflow Symfony state `WorkItem.billed` exposé pour contrôle.

**Décision PO** : ☐ A ☐ B ☐ C ☐ D ☐ B+D ☐ Autre :

#### Q2.3 — Override édition admin ?

Si édition bloquée (Q2.2) → admin (`ROLE_ADMIN`) peut forcer ?

| Option | Comportement |
|---|---|
| **A** — Admin override autorisé + audit log | Force édition + log dans `AuditLog` |
| **B** — Admin override bloqué + canal correction comptable | Pas de modif post-facturation, correction = invoice rectificative |

**Recommandation Tech Lead** : **A** sprint-021. **B** pour comptabilité stricte audit-trail mais coût UX élevé. Décision conservatrice = A + audit log obligatoire.

**Décision PO** : ☐ A ☐ B

#### Q2.4 — Seuil journalier dépassé (`DailyHoursExceededException`) ?

UC `RecordWorkItem` valide `dailyTotal <= dailyMaxHours` (ADR-0015). Si dépassé :

| Option | Comportement UI |
|---|---|
| **A** — Bloqué hard avec message | Saisie impossible, message clair |
| **B** — Warning + override user | Confirmation utilisateur force saisie |
| **C** — Bloqué user + override admin | User bloqué, admin force si nécessaire |

**Recommandation Tech Lead** : **C**. Sécurité par défaut + flexibilité admin. Aligné Q2.3 admin override pattern.

**Décision PO** : ☐ A ☐ B ☐ C

---

### Bloc 3 — Workflow state machine `WorkItem`

#### Q3.1 — États MVP sprint-021 ?

ADR-0013 propose 4 états : `draft → validated → billed → paid`. Sprint-021 MVP scope :

| Option | États | Transitions sprint-021 |
|---|---|---|
| **A** — 4 états dès Phase 3 | draft / validated / billed / paid | `validate`, `bill`, `mark_paid` |
| **B** — 2 états MVP + extension Phase 4 | draft / validated | `validate` only |
| **C** — Pas de Workflow Phase 3, ajouter Phase 4 | aucun (status = string libre) | aucune |

**Recommandation Tech Lead** : **B**. États `billed` + `paid` requièrent intégration `Invoice` BC (cross-aggregate) → sprint-022+ après UC `CalculateProjectMargin` qui fait pont. Phase 3 focalisé saisie + validation.

**Décision PO** : ☐ A ☐ B ☐ C

#### Q3.2 — Transition `validate` automatique ou manuelle ?

| Option | Trigger |
|---|---|
| **A** — Auto sur submit user (Q2.1 = A) | WorkItem créé directement `validated` |
| **B** — Auto sur submit + revue hebdo manager | Manager déclenche `validate` après revue |
| **C** — Validation manager obligatoire avant calcul marge | UC `CalculateProjectMargin` ignore `draft` |

**Recommandation Tech Lead** : **A** sprint-021 MVP. **B**/**C** = workflow approbation = scope sprint-022+ si demande PO réelle.

**Décision PO** : ☐ A ☐ B ☐ C

---

### Bloc 4 — UC `CalculateProjectMargin` trigger + scope

#### Q4.1 — Trigger calcul marge ?

| Option | Trigger | Latence | Coût compute |
|---|---|---|---|
| **A** — Temps réel sur ajout `WorkItem` | Sync dans UC `RecordWorkItem` | Immédiat | Élevé (chaque save) |
| **B** — Async via Domain Event | `WorkItemRecorded` → handler async | < 10s | Modéré |
| **C** — Batch quotidien (cron) | Tous projets recalculés 1x/jour | Jusqu'à 24h | Faible |
| **D** — On-demand (lazy) | Calcul à consultation projet | Au moment lecture | Modéré |
| **E** — Hybride : B + D fallback | Async + recalcul lecture si stale > X heures | < 10s typique | Modéré |

**Recommandation Tech Lead** : **B** (async via `WorkItemRecorded` Domain Event). Pattern Symfony Messenger déjà en place (US-094). Latence < 10s acceptable. **A** trop coûteux. **C** trop lent vs ADR-0013 promesse « temps réel ». **D** masque problèmes silencieux. **E** complexe.

**Décision PO** : ☐ A ☐ B ☐ C ☐ D ☐ E

#### Q4.2 — Scope calcul marge MVP ?

ADR-0013 définit `marge = sum(coût heures) - sum(facturé payé)`. Scope sprint-021 inclus :

| Composant | Inclus MVP ? | Justification |
|---|:---:|---|
| Coût heures = sum(`WorkItem.hours × hourlyRate`) | ✅ | Cœur calcul |
| Facturé = sum(`Invoice.total WHERE status = paid`) | ✅ | Cash réellement encaissé |
| Facturé issued non payé (`status = billed`) | ☐ | À discuter — affiche % facturable |
| Forecast (heures restantes × hourlyRate) | ❌ post-MVP | Sprint-023+ si demande |
| Coûts indirects (overhead, frais structure) | ❌ post-MVP | Sprint-024+ si demande |
| Avenants / refacturations | ❌ post-MVP | Edge cases ADR-0013 négatif |

**Recommandation Tech Lead** : Inclus MVP = ✅ uniquement. Inclure `billed` non payé brouille marge cash réelle. Post-MVP traction-driven.

**Décision PO** : ☐ Liste ci-dessus ☐ Inclure billed non payé ☐ Autres :

#### Q4.3 — Méthodes exposées sur `Project` aggregate ?

| Méthode | Retour |
|---|---|
| `getMargeAbsolute()` | `Money` (coût - facturé) |
| `getMargePercent()` | `?float` (marge / facturé × 100) |
| `getCoutTotal()` | `Money` (sum coût) |
| `getFactureTotal()` | `Money` (sum facturé) |
| `getMargeCalculatedAt()` | `?DateTimeImmutable` (dernière maj) |

**Recommandation Tech Lead** : tous les 5. Pattern aligné `BusinessKpiService` US-093.

**Décision PO** : ☐ Liste complète ☐ Réduire :

---

### Bloc 5 — Seuil alerte marge + EmploymentPeriod data quality

#### Q5.1 — Seuil dépassement marge configurable ?

ADR-0013 mentionne `MarginThresholdExceededEvent` quand marge < seuil. Granularité ?

| Option | Granularité | Configuration |
|---|---|---|
| **A** — Global single seuil (ex < 10 %) | Tous projets | Variable env / config bundle |
| **B** — Par client | Override par `Client` aggregate | Champ `Client.marginThreshold` |
| **C** — Par projet | Override par `Project` aggregate | Champ `Project.marginThreshold` |
| **D** — Hiérarchique : global default + override client + override projet | Cascade | 3 niveaux |

**Recommandation Tech Lead** : **A** sprint-022 MVP (ce sprint focalisé sur RecordWorkItem). **D** = scope sprint-023+ si demande.

**Décision PO** : ☐ A ☐ B ☐ C ☐ D — Sprint-021 OU sprint-022 ?

#### Q5.2 — Valeur seuil par défaut ?

Si A choisi (global), valeur ?

| Option | Seuil |
|---|---|
| **A** — < 5 % marge | Très permissif (alerte rare) |
| **B** — < 10 % marge | Standard agence |
| **C** — < 15 % marge | Conservateur |
| **D** — < 20 % marge | Très conservateur |

**Recommandation Tech Lead** : **B** (10 %). Standard secteur agence/services.

**Décision PO** : ☐ A ☐ B ☐ C ☐ D ☐ Autre : %

#### Q5.3 — EmploymentPeriod data quality — audit `--audit-daily-hours` ?

ADR-0015 A-6 mentionne audit étendu si `EmploymentPeriod.weeklyHours` ou `workTimePercentage` NULL/aberrant. Risk : invariant journalier inopérant.

| Option | Action sprint-021 |
|---|---|
| **A** — Étendre script audit + correction admin AVANT déploiement Phase 3 | 1 pt sub-epic D sprint-021 |
| **B** — Déployer sans audit, monitorer `DailyHoursExceededException` triggered/sem | 0 pt — risque silencieux |
| **C** — Audit après déploiement, correction itérative | 0.5 pt sprint-022 |

**Recommandation Tech Lead** : **A**. Pattern AUDIT-CONTRIBUTORS-CJM sprint-020 succès → reproduire pour `EmploymentPeriod`. ROI préventif.

**Décision PO** : ☐ A ☐ B ☐ C

---

### Bloc 6 — Backlog sprint-021 ferme + capacité

#### Q6.1 — Capacité sprint-021 = 12 pts ?

Sprint-019 = 12, sprint-020 = 12 (ferme 10 + libre 2). Continuer 12 ?

**Décision PO** : ☐ 10 ☐ 12 ☐ 14 ☐ Autre :

#### Q6.2 — Backlog ferme sprint-021 selon décisions blocs 1-5 ?

Estimation indicative (à ajuster selon décisions) :

| Story | Bloc | Pts |
|---|---|---:|
| US-099 UC `RecordWorkItem` + invariant journalier | 1 + 2 + 4 | 5 |
| US-100 Domain Service `DailyHoursValidator` + `DailyHoursExceededException` | 2 + 4 | 2 |
| US-101 Workflow Symfony state machine `WorkItem` MVP | 3 | 2 |
| US-102 UI Twig saisie (selon Q1.1) | 1 | 2-5 |
| AUDIT-DAILY-HOURS (si Q5.3 A) | 5 | 1 |
| OPS-PREP-J0 atelier J-2 sprint-021 | (héritage sprint-020 retro A-2) | 0 (rituel) |
| **Total estimation** | | **12-15 pts** |

Si total > capacité, arbitrage PO :
- Reporter UI (US-102) → sprint-022, MVP UC + API only ?
- Reporter Workflow state machine (US-101) → sprint-022 ?
- Reporter audit `--audit-daily-hours` (Q5.3 = B) ?

**Décision PO** : Backlog ferme sprint-021 =

#### Q6.3 — Sub-epic B OPS holdover (sprint-020 retro A-4) ?

Décision finale Slack webhook + Sentry alerts + SMOKE config :

| Option | Action |
|---|---|
| **A** — Owner unique fixé J0 + go sprint-021 | Tech Lead OU PO backup activé immédiatement |
| **B** — Out backlog | Sortir EPIC-002 stragglers, replanifier sprint dédié quand owner aligné |
| **C** — Réallocation : 1 sub-task / sprint sur 3 sprints | Slack sprint-021 / Sentry sprint-022 / SMOKE sprint-023 |

**Recommandation Tech Lead** : **A** + application stricte runbook OPS-PREP-J0. Si owner pas confirmé J0 → **B** (Out backlog, 4ᵉ holdover signal arrêt).

**Décision PO** : ☐ A ☐ B ☐ C

#### Q6.4 — Capacité libre sprint-021 — pré-allocation explicite (sprint-020 retro A-5) ?

| Story candidate | Pts | Justification |
|---|---:|---|
| EPIC-003 Phase 3+ démarrage : `MarginThresholdExceededEvent` + alerte Slack | 2-3 | Sprint-022 anticipé |
| TEST-COVERAGE-011 Domain Notification + Settings BCs | 2 | Coverage 65 → 68 % step 11 |
| ADR-0016 décisions Phase 3 (livrable atelier ce jour) | 0.5 | Documentation décisions PO |

**Décision PO** : Pré-allocation =

---

## 5. Livrables atelier

À produire fin atelier J0 sprint-021 :

| Livrable | Owner | Format |
|---|---|---|
| **ADR-0016** EPIC-003 Phase 3 décisions | Tech Lead | Markdown ADR pattern 0013/0015 |
| **Sprint-021 sprint-goal.md** | Tech Lead | Pattern sprint-019/020 |
| **Backlog sprint-021 décomposé** | Tech Lead + PO | sub-epics A-D |
| **Stories US-099..US-102** spécifiées (3C + Gherkin) | PO | `project-management/backlog/user-stories/*.md` |

---

## 6. Risks atelier

| Risk | Mitigation |
|---|---|
| Décisions UI Q1.1 sous-estimées effort sprint-021 | Re-estimer fin atelier avec stories décomposées |
| EmploymentPeriod data prod plus dégradée qu'attendu | Q5.3 A → audit avant deploy |
| PO indispo atelier J0 → sprint-021 commence sans cap décisions | Reporter Phase 3 sprint-022, sprint-021 = buffer + dette tech |
| Sub-epic B OPS holdover 5ᵉ sprint si Q6.3 mal tranché | Application stricte runbook OPS-PREP-J0 — si owner pas J0 → Out |

---

## 7. Liens

- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- ADR-0015 — EPIC-003 Phase 2 décisions task=NULL + doublons + invariant journalier
- Audit data : `docs/02-architecture/epic-003-audit-existing-data.md`
- Runbook OPS-PREP-J0 : `docs/runbooks/sprint-ops-prep-j0.md`
- Sprint-020 retro action A-3 : `sprint-retro.md`
- Sprint-020 review §Feedback PO : `sprint-review.md`

---

**Auteur** : Tech Lead
**Date prep** : 2026-05-09
**Version** : 1.0.0
**Sprint origine prep** : 020 retro action A-3
