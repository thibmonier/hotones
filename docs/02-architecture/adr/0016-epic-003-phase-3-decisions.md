# ADR-0016 — EPIC-003 Phase 3 : décisions UC RecordWorkItem + Workflow + UI saisie

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-09 |
| Sprint | sprint-021 atelier PO Phase 3 J0 |
| Story | sprint-020 retro A-3 héritage |
| Auteur | Tech Lead + PO (atelier sprint-021 J0) |

---

## Contexte

Atelier prep PO Phase 3 (`project-management/sprints/sprint-020-epic-003-phase-2-acl/atelier-po-phase-3-prep.md`)
livré sprint-020 PR #209 — 6 blocs / 19 questions structurées options + reco
Tech Lead.

EPIC-003 Phase 1 (sprint-019 #200) + Phase 2 (sprint-020 #207) livrés.
Phase 3 = mutation Domain via UC `RecordWorkItem` + Workflow Symfony state
machine + UI saisie hebdo + UC `CalculateProjectMargin`.

ADR captures décisions PO atelier sprint-021 J0 → backlog sprint-021 ferme
17 pts.

---

## Décisions PO

### Bloc 1 — UC `RecordWorkItem` UI/UX scope

#### Q1.1 — Surface saisie : **A** (Twig grille hebdo jours × projets)

**Décision** : grille hebdo type tableur (drag-drop, total auto par jour
+ par projet). Pattern UX riche.

**Justification PO** : workflow saisie réel agence — vue hebdo permet
contrôler totaux journaliers + équilibrage projets. Formulaire ligne-par-ligne
trop friction.

**Impact estimation** : US-102 = 5 pts (vs 2 pts si formulaire — option B
écartée).

#### Q1.2 — Champs MVP : **liste 5 + comment optionnel** (reco TL)

| Champ | Type | Statut MVP |
|---|---|---|
| `date` | DateImmutable | ✅ obligatoire |
| `contributorId` | Auto (user connecté) | ✅ obligatoire |
| `projectId` | Sélection liste | ✅ obligatoire |
| `taskId` | ?ProjectTaskId nullable (ADR-0015 Q1) | ☐ optionnel |
| `hours` | WorkedHours decimal | ✅ obligatoire |
| `comment` | ?string nullable | ☐ optionnel |
| `tags` | ?string[] | ❌ post-MVP (sprint-022+) |

#### Q1.3 — Step heures : **A** (0.25h / 15 min)

**Décision** : précision 15 min. VO `WorkedHours` Phase 1 supporte (precision 2 décimales).

---

### Bloc 2 — Validation + édition + verrouillage

#### Q2.1 — Soumission saisie : **A** (auto-save ligne par ligne)

**Décision** : submit implicite après chaque save grille hebdo. Pas de bouton « Soumettre semaine ».

**Justification** : aligné Q1.1 grille hebdo (UX fluide drag-drop + auto-save).

#### Q2.2 — Édition rétroactive : **B+D** (reco TL — 7 jours OU bloqué post-facturation)

**Décision** : édition WorkItem autorisée si :
- Date saisie ≤ 7 jours en arrière
- ET projet associé pas encore facturé (`Invoice.status != billed/paid`)

#### Q2.3 — Override édition admin : **A** (reco TL — autorisé + audit log)

**Décision** : `ROLE_ADMIN` peut force-éditer WorkItem même si Q2.2 bloque.
Toute modification admin loggée dans `AuditLog` (qui, quoi, avant/après,
quand).

#### Q2.4 — Seuil journalier dépassé : **B** (warning + override user)

⚠️ **Décision diverge reco TL** (qui était C — bloqué user + override admin).

**Décision** : si `dailyTotal > dailyMaxHours`, warning UI affiché +
checkbox confirmation utilisateur force la saisie. Audit log conserve le
fait (motif override loggé).

**Justification PO** : flexibilité saisie > rigidité. Cas légitimes
fréquents (heures sup, sous-traitance ponctuelle).

**Impact** : `DailyHoursExceededException` Domain = `Warning` non bloquant
côté UC (vs Exception bloquante reco). Renommer + adapter logique :
`DailyHoursWarningEvent` retourné UC, UI choisit comportement.

---

### Bloc 3 — Workflow state machine `WorkItem`

#### Q3.1 — États MVP sprint-021 : **A** (4 états dès Phase 3)

⚠️ **Décision diverge reco TL** (qui était B — 2 états MVP).

**Décision** : `draft → validated → billed → paid`. Workflow Symfony complet
livré sprint-021.

**Impact** : cross-aggregate Invoice (transitions `bill` + `mark_paid`
nécessitent lecture `Invoice` BC). US-101 = 4 pts (vs 1-2 pts si MVP 2 états).

**Implémentation** :
- `bill` : trigger sur `InvoiceCreatedEvent` (ACL Invoice → WorkItem)
- `mark_paid` : trigger sur `InvoicePaidEvent` (ACL Invoice → WorkItem)
- Domain Event Listener dans Application Layer

#### Q3.2 — Transition `validate` : **A role-based managers**

⚠️ **Décision spécifique** (variante reco TL).

**Décision** : transition `validate` automatique sur submit user **uniquement
si user a `ROLE_MANAGER` ou `ROLE_ADMIN`**. Sinon WorkItem reste `draft`
jusqu'à validation explicite manager.

**Interprétation Tech Lead** (à valider PO) :
- User `ROLE_MANAGER` ou `ROLE_ADMIN` : submit → WorkItem direct `validated`
- User non-manager : submit → WorkItem `draft`, manager déclenche `validate` après revue

**Implémentation** :
- UC `RecordWorkItem` reçoit `User $author` injecté
- Si `$author->hasRole('ROLE_MANAGER') || $author->hasRole('ROLE_ADMIN')` →
  transition workflow `validate` auto-déclenchée dans même UC
- Sinon → WorkItem persisté en `draft`

UI manager : page « Validation hebdo » liste WorkItem `draft` équipe →
bouton `validate` par item ou bulk.

⚠️ **Question ouverte PO à confirmer** : qui peut être manager d'un WorkItem
spécifique ? Manager direct du contributeur ? Tous managers ? Manager projet ?

---

### Bloc 4 — UC `CalculateProjectMargin`

#### Q4.1 — Trigger calcul : **B** (reco TL — async via Domain Event)

**Décision** : `WorkItemRecorded` Domain Event dispatché sync UC, handler
async (Symfony Messenger) recalcule marge projet. Latence < 10s acceptable.

**Implémentation** : `RecalculateProjectMarginOnWorkItemRecorded` handler
async (transport `async_margin` via Redis Messenger).

#### Q4.2 — Scope calcul MVP : **liste 2 inclus** (reco TL)

| Composant | Inclus MVP ? |
|---|:---:|
| Coût heures = `sum(WorkItem.hours × hourlyRate)` | ✅ |
| Facturé = `sum(Invoice.total WHERE status = paid)` | ✅ |
| Facturé `billed` non payé | ❌ post-MVP |
| Forecast (heures restantes × hourlyRate) | ❌ post-MVP |
| Coûts indirects (overhead) | ❌ post-MVP |
| Avenants / refacturations | ❌ post-MVP |

#### Q4.3 — Méthodes Project aggregate : **liste 5** (reco TL)

| Méthode | Retour |
|---|---|
| `getMargeAbsolute()` | `Money` (coût - facturé) |
| `getMargePercent()` | `?float` (marge / facturé × 100) |
| `getCoutTotal()` | `Money` |
| `getFactureTotal()` | `Money` |
| `getMargeCalculatedAt()` | `?DateTimeImmutable` |

---

### Bloc 5 — Seuil alerte marge + EmploymentPeriod data quality

#### Q5.1 — Granularité seuil : **A** (global single seuil)

**Décision** : valeur seuil unique globale (variable `MARGIN_ALERT_THRESHOLD`
config bundle). Pas d'override par client / projet sprint-021.

⚠️ **Timing** : Tech Lead reco initiale = sprint-022 MVP (sprint-021 focalisé
RecordWorkItem). **Décision retenue par défaut : sprint-022** sauf contre-ordre PO.

**Hiérarchique override** (D — par client / projet) reporté sprint-023+ si
demande PO réelle après mesure adoption.

#### Q5.2 — Valeur seuil défaut : **B** (< 10 % marge)

**Décision** : seuil alerte `MarginThresholdExceededEvent` déclenché si
marge projet < 10 %.

**Configuration** : `MARGIN_ALERT_THRESHOLD=0.10` env var (override possible).

#### Q5.3 — Audit `--audit-daily-hours` : **A** (reco TL — étendre + correction admin AVANT deploy Phase 3)

**Décision** : étendre script `app:audit:contributors-cjm` avec flag
`--audit-daily-hours` pour détecter `EmploymentPeriod.weeklyHours` ou
`workTimePercentage` NULL/aberrant.

Output script : liste contributors avec config invalide. Plan correction admin
exécuté **avant** déploiement Phase 3 prod.

**Impact estimation** : sub-epic C sprint-021 = 1 pt.

---

### Bloc 6 — Backlog sprint-021 ferme + capacité

#### Q6.1 — Capacité sprint-021 : **17 pts ferme** (challenge vélocité)

⚠️ **Décision divergente** : capacité Q6.1 = 10 pts initialement répondu
par PO mais Q6.2 « augmentation capacité si possible » + estimation backlog
post-décisions = 17 pts → arbitrage final **17 pts ferme**.

**Justification** : décisions Q1.1 (UI grille hebdo +3 pts) + Q3.1 (Workflow
4 états +2 pts) augmentent scope. PO accepte challenge vélocité vs splitting
sprints.

**Risk visible** : vélocité moyenne sprint-019/020 = ~10 pts. 17 pts =
+70 % vs moyenne. Risque holdover sprint-022. Métrique surveillée retro.

#### Q6.2 — Backlog ferme + augmentation capacité : **acté 17 pts**

**Décision** : pas de descope. Capacité augmentée pour absorber décisions
Q1.1 + Q3.1.

#### Q6.3 — Sub-epic B OPS holdover : **A** (reco TL — owner J0 fixé + go SI confirmé sinon B Out backlog)

**Décision** : application stricte runbook OPS-PREP-J0 sprint-021 J-2.
- Si owner Tech Lead OU PO backup confirmed J0 + access Slack workspace +
  Sentry org admin + GH repo Settings → **A** (go sprint-021 sub-epic ferme +0.5 pt holdover restant)
- Sinon → **B** (Out backlog, replanifier sprint dédié OPS quand owner aligné)

**Décision finale** : à acter atelier OPS-PREP-J0 J-2 (2026-05-10).

#### Q6.4 — Capacité libre : **MarginThresholdExceededEvent + alerte Slack** (2-3 pts)

**Décision** : capacité libre 2-3 pts allouée Domain Event
`MarginThresholdExceededEvent` + handler async alerte Slack `#alerts-prod`
via `SlackAlertingService` (US-094 sprint-017).

**Seuil** : valeur défaut Q5.2 = 10 % marge.

**Note** : configurabilité hiérarchique (Q5.1 D) reportée sprint-022.

---

## Conséquences design DDD

### Aggregate `WorkItem` (Phase 1 livré sprint-019, étendu Phase 2 sprint-020)

Phase 3 ajouts :
- `WorkItemStatus` enum Workflow Symfony (4 états Q3.1) : `DRAFT`, `VALIDATED`, `BILLED`, `PAID`
- Méthode `markAsValidated()` + `markAsBilled()` + `markAsPaid()` Aggregate Root
- Event `WorkItemRecorded` (lieu de `WorkItemCreated` Phase 1 si applicable)

### UC `RecordWorkItem` (sprint-021 sub-epic A)

```
RecordWorkItem::execute(command, author):
    1. existingItems = repo.findByContributorAndDate(command.contributorId, command.date)
    2. dailyTotal = sum(existingItems.hours) + command.hours
    3. dailyMaxHours = dailyHoursValidator.dailyMaxHours(command.contributorId, command.date)
    4. workItem = WorkItem::create(...) avec status DRAFT
    5. Si Q3.2 author->hasRole('ROLE_MANAGER' OR 'ROLE_ADMIN') :
         workflow.apply(workItem, 'validate') → status VALIDATED
    6. Si Q2.4 dailyTotal > dailyMaxHours ET command.userOverride === false :
         throw DailyHoursWarningException (UI affiche + propose override)
       Si command.userOverride === true :
         log audit override
    7. repo.save(workItem)
    8. dispatch WorkItemRecorded event (sync) → handler async marge
```

### Domain Service `DailyHoursValidator` (sprint-021 sub-epic A)

Encapsule calcul `dailyMaxHours` depuis `EmploymentPeriod` :
```
dailyMaxHours = (EmploymentPeriod.weeklyHours × workTimePercentage / 100) / 5
```

⚠️ Q5.3 audit garantit data quality avant deploy.

### Workflow Symfony state machine `work_item` (sprint-021 sub-epic A)

```yaml
framework:
    workflows:
        work_item:
            type: state_machine
            marking_store:
                type: method
                property: status
            supports:
                - App\Domain\WorkItem\Entity\WorkItem
            initial_marking: draft
            places: [draft, validated, billed, paid]
            transitions:
                validate: { from: draft, to: validated }
                bill: { from: validated, to: billed }
                mark_paid: { from: billed, to: paid }
```

Listeners cross-aggregate Invoice :
- `InvoiceCreatedEvent` → `BillRelatedWorkItems` (Application Layer ACL)
- `InvoicePaidEvent` → `MarkRelatedWorkItemsAsPaid` (Application Layer ACL)

### UI Twig grille hebdo (sprint-021 sub-epic B)

Vue `/timesheet/{week}` :
- Grille 7 jours × N projets
- Drag-drop saisie heures
- Auto-save (Q2.1 = A)
- Total auto par jour + par projet
- Warning visuel si Q2.4 dépassement journalier (override checkbox)
- Édition désactivée selon Q2.2 (date > 7 jours OU projet billed/paid)

### UC `CalculateProjectMargin` (sprint-022 — capacité libre Q6.4 sprint-021 ?)

⚠️ Décision PO atelier J0 : Q4.x décrit le UC mais sprint-021 capacité libre
Q6.4 = `MarginThresholdExceededEvent` + alerte Slack (pas le UC complet).

Interprétation Tech Lead : sprint-021 capacité libre livre l'event + alerting.
UC `CalculateProjectMargin` qui calcule la marge = sprint-022 (avec
configurabilité Q5.1 D si choisie).

⚠️ **Question ouverte PO** : sprint-021 = event + alerting (sans UC complet
encore) suffit ? OU il faut UC complet sprint-021 ?

---

## Trigger réversibilité

Reconsidérer décisions si :

| Décision | Trigger reconsidération |
|---|---|
| Q1.1 grille hebdo | Adoption < 50 % users à 1 mois prod (signal UX trop complexe) |
| Q2.4 warning override | > 30 % overrides utilisés / sem (signal seuil journalier mal calibré) |
| Q3.1 4 états | Cross-aggregate Invoice trop complexe sprint-021 (drop billed/paid → revert 2 états) |
| Q3.2 role-based managers | Process validation manager déphasé saisie réelle (drop role-based, all auto-validated) |
| Q5.2 seuil 10 % | Alerte trop bruyante (> 30 alertes/sem) OU silencieuse (< 1/mois) → ajuster |
| Q6.1 17 pts ferme | Holdover > 5 pts sprint-021 → recalibrer sprint-022 baseline 12 pts |

---

## Action items

| ID | Action | Owner | Sprint |
|---|---|---|---|
| A-1 | Étendre `WorkItem` Phase 1+2 avec `WorkItemStatus` enum 4 états | Tech Lead | sprint-021 US-101 |
| A-2 | Configurer Workflow Symfony `work_item` state machine | Tech Lead | sprint-021 US-101 |
| A-3 | UC `RecordWorkItem` avec invariant journalier + warning Q2.4 + role-based Q3.2 | Tech Lead | sprint-021 US-099 |
| A-4 | Domain Service `DailyHoursValidator` + `DailyHoursWarningException` | Tech Lead | sprint-021 US-100 |
| A-5 | UI Twig grille hebdo saisie + auto-save | Tech Lead | sprint-021 US-102 |
| A-6 | Étendre script audit `--audit-daily-hours` | Tech Lead | sprint-021 sub-epic C |
| A-7 | `MarginThresholdExceededEvent` + handler Slack alerting | Tech Lead | sprint-021 capacité libre Q6.4 |
| A-8 | UC `CalculateProjectMargin` complet | Tech Lead | sprint-022 |
| A-9 | Configurabilité hiérarchique seuil marge (Q5.1 D) | Tech Lead | sprint-023+ si traction |
| A-10 | Cross-aggregate Invoice→WorkItem listeners (`bill`/`mark_paid`) | Tech Lead | sprint-021 US-101 |

---

## ⚠️ Questions ouvertes à valider PO

| ID | Question | Default Tech Lead |
|---|---|---|
| OQ-1 | Q3.2 interprétation : managers self-validate via ROLE_MANAGER + ROLE_ADMIN ? | ✅ Oui (à confirmer) |
| OQ-2 | Q3.2 « manager d'un WorkItem » : manager direct contributor / tous managers / manager projet ? | Manager direct contributor (à confirmer) |
| OQ-3 | Q5.1 timing : alerte marge configurable sprint-022 (default) OU sprint-021 ? | Sprint-022 |
| OQ-4 | Q6.4 capacité libre : event + alerting sprint-021 OU UC `CalculateProjectMargin` complet sprint-021 ? | Event + alerting sprint-021 (UC complet sprint-022) |

---

## Alternatives écartées

### Q1.1 — Option B (formulaire ligne-par-ligne)
**Écarté** : workflow saisie réel agence requiert vue hebdo. Friction
ligne-par-ligne casse UX.

### Q3.1 — Option B (2 états MVP)
**Écarté** : PO accepte challenge complexité cross-aggregate Invoice pour
livrer Workflow complet sprint-021. Évite refactor sprint-022.

### Q3.2 — Option A pure (auto sans role)
**Écarté** : process validation manager existe agence — must respect
hiérarchie.

### Q3.2 — Option C (validation manager obligatoire pré-marge)
**Écarté** : trop rigide. Managers self-saisissent fréquemment.

### Q6.1 — 10-12 pts (capacité standard)
**Écarté** : décisions atelier augmentent scope, PO préfère challenge
vélocité vs descope.

---

## Conséquences

### Positives
- ✅ UC `RecordWorkItem` complet livré sprint-021 (saisie + invariant + workflow + UI)
- ✅ UX riche grille hebdo (vs friction formulaire)
- ✅ Workflow 4 états livré dès sprint-021 (vs refactor sprint-022)
- ✅ Process validation manager respecté (Q3.2 role-based)
- ✅ Sécurité invariant journalier flexible (Q2.4 warning override + audit)
- ✅ Alerte marge sprint-021 capacité libre (vs sprint-022 délai)

### Négatives
- ❌ **Sprint-021 17 pts ferme = +70 % vélocité moyenne** — risk holdover
- ❌ Cross-aggregate Invoice→WorkItem complexe (Q3.1 + A-10)
- ❌ UI grille hebdo coûteuse (Q1.1 = 5 pts vs formulaire 2 pts)
- ❌ Override seuil journalier (Q2.4) peut masquer abus si audit log non monitoré
- ❌ 4 questions ouvertes (OQ-1..OQ-4) à valider PO avant Sprint Planning P2

---

## Liens

- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- ADR-0015 — EPIC-003 Phase 2 décisions task=NULL + doublons + invariant journalier
- Atelier prep PO Phase 3 : `project-management/sprints/sprint-020-epic-003-phase-2-acl/atelier-po-phase-3-prep.md`
- Sprint-020 retro action A-3 : `project-management/sprints/sprint-020-epic-003-phase-2-acl/sprint-retro.md`
- Sprint-021 sprint-goal : `project-management/sprints/sprint-021-epic-003-phase-3/sprint-goal.md`
- Runbook OPS-PREP-J0 : `docs/runbooks/sprint-ops-prep-j0.md`
- US-094 SlackAlertingService (réutilisé Q6.4)

---

**Date de dernière mise à jour :** 2026-05-09
**Version :** 1.0.0
