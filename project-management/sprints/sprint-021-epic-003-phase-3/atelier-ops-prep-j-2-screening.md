# Atelier OPS-PREP-J0 J-2 Screening — Sprint-021

| Champ | Valeur |
|---|---|
| Sprint cible | 021 (EPIC-003 Phase 3) |
| Date prévue | 2026-05-10 (J-2 sprint kickoff) |
| Durée | ~30 min |
| Participants | PO + Tech Lead |
| Owner doc | Tech Lead |
| Origine | Sprint-020 retro action A-2 + runbook OPS-PREP-J0 §1 |
| Livrable | Décision go/risk/OUT par story candidate sprint-021 |

---

## 1. Méthode

Runbook `docs/runbooks/sprint-ops-prep-j0.md` §2 — pour chaque story
candidate sprint-021, répondre 6 questions (Q1-Q6 credentials/console
admin/données prod/config infra/coordination tierce/dépendances).

Décision finale : ✅ go / 🟡 risk flagged / 🔴 OUT sprint.

**Règle d'or** : aucune story OPS-tagged ne rentre sprint-021 sans
confirmation J0 explicite.

---

## 2. Stories candidates sprint-021

5 stories backlog ferme + 1 capacité libre :

- US-099 UC `RecordWorkItem`
- US-100 Domain Service `DailyHoursValidator` + `DailyHoursWarningException`
- US-101 Workflow Symfony state machine 4 états + cross-aggregate Invoice
- US-102 UI Twig grille hebdo
- AUDIT-DAILY-HOURS audit script
- US-103 (libre) `MarginThresholdExceededEvent` + alerte Slack

Plus : OPS-DECISION-B sub-epic B holdover sprint-019/020.

---

## 3. Screening par story

### US-099 UC `RecordWorkItem`

| Q | Question | Réponse | Owner | Statut J0 |
|---|---|---|---|---|
| Q1 | Credentials externes ? | ❌ aucun | — | — |
| Q2 | Console admin tierce ? | ❌ aucune | — | — |
| Q3 | Données prod ? | ❌ aucune (Domain pure + Integration test BDD) | — | — |
| Q4 | Config infra ? | ❌ aucune | — | — |
| Q5 | Coordination tierce ? | ❌ aucune | — | — |
| Q6 | Dépendances bloquantes ? | ✅ ADR-0015 invariant journalier livré + Phase 2 #207 mergée + Q3.2 OQ-1 + OQ-2 (Sprint Planning P2) | Tech Lead | ⏳ OQ-1+OQ-2 |

**Décision J0** : ✅ go (sous réserve OQ-1 + OQ-2 validation Sprint Planning P2 — non bloquant Sprint Planning P1)

---

### US-100 Domain Service `DailyHoursValidator` + `DailyHoursWarningException`

| Q | Question | Réponse | Owner | Statut J0 |
|---|---|---|---|---|
| Q1 | Credentials externes ? | ❌ aucun | — | — |
| Q2 | Console admin tierce ? | ❌ aucune | — | — |
| Q3 | Données prod ? | ❌ aucune (Domain pure + Integration test) | — | — |
| Q4 | Config infra ? | ❌ aucune | — | — |
| Q5 | Coordination tierce ? | ❌ aucune | — | — |
| Q6 | Dépendances bloquantes ? | ✅ `EmploymentPeriodRepository` interface Domain (existante OU à créer Phase 3) | Tech Lead | confirmer existence repo |

**Décision J0** : ✅ go

**Action prep J0** : Tech Lead vérifie `EmploymentPeriodRepository` interface
Domain existe. Si non → ajout Phase 3 task US-100 spécifié.

---

### US-101 Workflow Symfony state machine + cross-aggregate Invoice

| Q | Question | Réponse | Owner | Statut J0 |
|---|---|---|---|---|
| Q1 | Credentials externes ? | ❌ aucun | — | — |
| Q2 | Console admin tierce ? | ❌ aucune | — | — |
| Q3 | Données prod ? | ❌ aucune | — | — |
| Q4 | Config infra ? | 🟡 modif `config/packages/workflow.yaml` (nouveau bundle config) | Tech Lead | aucun blocker |
| Q5 | Coordination tierce ? | ❌ aucune | — | — |
| Q6 | Dépendances bloquantes ? | ✅ `Invoice` BC events (`InvoiceCreatedEvent`, `InvoicePaidEvent`) — vérifier existants OU à ajouter Phase 3 | Tech Lead | confirmer |

**Décision J0** : ✅ go

**Action prep J0** : Tech Lead vérifie events `InvoiceCreatedEvent` +
`InvoicePaidEvent` existent côté Invoice BC. Si non → +task spec US-101.

---

### US-102 UI Twig grille hebdo

| Q | Question | Réponse | Owner | Statut J0 |
|---|---|---|---|---|
| Q1 | Credentials externes ? | ❌ aucun | — | — |
| Q2 | Console admin tierce ? | ❌ aucune | — | — |
| Q3 | Données prod ? | ❌ aucune (UI test E2E Panther local Docker) | — | — |
| Q4 | Config infra ? | ❌ aucune | — | — |
| Q5 | Coordination tierce ? | 🟡 UX/design feedback PO sur grille hebdo (drag-drop, layout) | PO | non bloquant — itération in-sprint |
| Q6 | Dépendances bloquantes ? | ✅ US-099 UC `RecordWorkItem` (US-102 consume UC pour POST saisie) | Tech Lead | ordre exécution sprint = US-099 → US-102 |

**Décision J0** : ✅ go (avec ordre US-099 → US-102 figé sprint backlog)

---

### AUDIT-DAILY-HOURS audit script

| Q | Question | Réponse | Owner | Statut J0 |
|---|---|---|---|---|
| Q1 | Credentials externes ? | ❌ aucun (CLI command) | — | — |
| Q2 | Console admin tierce ? | ❌ aucune | — | — |
| Q3 | Données prod ? | ✅ **YES** — script lit `EmploymentPeriod` prod pour audit | Tech Lead | accès BDD prod read-only confirmé ? |
| Q4 | Config infra ? | ❌ aucune | — | — |
| Q5 | Coordination tierce ? | 🟡 admin BDD prod corrige données aberrantes post-audit (correction admin) | Tech Lead | plan correction post-output |
| Q6 | Dépendances bloquantes ? | ✅ Script `app:audit:contributors-cjm` existe (sprint-020 #205) — extension flag `--audit-daily-hours` | Tech Lead | base existante OK |

**Décision J0** : ✅ go (READ-ONLY audit non destructif)

**Action prep J0** :
- Tech Lead confirme accès Render BDD read-only prod (DATABASE_URL_READONLY ?)
- Plan correction admin documenté avant deploy Phase 3 (Q5.3 ADR-0016)

---

### US-103 `MarginThresholdExceededEvent` + alerte Slack (capacité libre)

| Q | Question | Réponse | Owner | Statut J0 |
|---|---|---|---|---|
| Q1 | Credentials externes ? | ✅ **YES** — Slack webhook URL `#alerts-prod` (variable env `SLACK_WEBHOOK_URL`) | PO ou Tech Lead | **bloquant si non configuré prod** |
| Q2 | Console admin tierce ? | ✅ Render dashboard pour env var push | Tech Lead | accès Render confirmé ? |
| Q3 | Données prod ? | ❌ aucune (event Domain + handler async) | — | — |
| Q4 | Config infra ? | ✅ env var `SLACK_WEBHOOK_URL` + `SLACK_ALERTS_CHANNEL=#alerts-prod` (déjà spécifiés `render.yaml`) | Tech Lead | `sync: false` → push manuel dashboard |
| Q5 | Coordination tierce ? | 🟡 Slack workspace admin pour create webhook si pas fait | PO | héritage sub-epic B holdover sprint-017→020 |
| Q6 | Dépendances bloquantes ? | ✅ US-094 `SlackAlertingService` (sprint-017) existant — réutilisé | Tech Lead | OK |

**Décision J0** : 🟡 **risk flagged**

**Risk** : Slack webhook URL **HÉRITAGE OPS holdover sub-epic B sprint-017→020**.
Si non configuré J0 → US-103 ne peut pas tester Slack alerting end-to-end
en prod (event dispatché mais alerte Slack silencieuse).

**Décision J0 alternatives** :
1. ✅ **A** — Webhook configuré J0 (PO ou Tech Lead crée + push var env Render
   dashboard) → US-103 + sub-epic B partiellement résolu (Slack OK, Sentry alert
   rules + SMOKE config restent OPS holdover)
2. 🟡 **B** — US-103 livré sans test prod end-to-end (event + handler unitaires
   testés, Slack alerting validé staging only) — webhook prod activé sub-epic B
   futur sprint
3. 🔴 **C** — US-103 OUT sprint-021, capacité libre réallouée TEST-COVERAGE-011

**Recommandation Tech Lead** : **A** si PO ou Tech Lead disponible J0 pour
créer webhook Slack workspace. Sinon **B** (delivery partiel). **C** = perte
capacité libre — éviter si possible.

---

### OPS-DECISION-B sub-epic B sprint-019/020 holdover

Décision finale (sprint-020 retro Q6.3 + ADR-0016) :

| Q | Question | Réponse | Owner | Statut J0 |
|---|---|---|---|---|
| Q1 | Credentials externes ? | ✅ **YES** — Slack webhook + Sentry org admin token + GH repo Settings (secrets push SMOKE_USER_*) | PO + Tech Lead | **bloquant si non confirmé J0** |
| Q2 | Console admin tierce ? | ✅ Slack workspace admin + Sentry org admin + Render dashboard + GH repo Settings | PO + Tech Lead | quadruple access |
| Q3 | Données prod ? | ✅ User smoke prod (DBA crée account + creds) | Tech Lead | DBA disponible ? |
| Q4 | Config infra ? | ✅ Render env vars + GH secrets/vars push | Tech Lead | quadruple config |
| Q5 | Coordination tierce ? | ✅ Slack workspace + Sentry org coordination | PO | aligned ? |
| Q6 | Dépendances bloquantes ? | ✅ Webhook Slack créé AVANT Sentry alert rules | Tech Lead | ordre figé |

**Décision J0** : runbook OPS-PREP-J0 §3 décision matrix appliquée.

| État credentials/access J0 | Décision sub-epic B sprint-021 |
|---|---|
| ✅ Tous confirmés (Slack + Sentry + DBA + GH) | A — Sub-epic B go +0.5 pt sprint-021 |
| 🟡 1-2 manquants, action immédiate possible | A — go + risk flagged daily |
| 🔴 Blocked owner / access pas obtenu J0 | B — **OUT sprint-021** (4ᵉ holdover = signal arrêt — replan sprint dédié OPS quand owner aligné) |

**Recommandation Tech Lead** : si tous confirmés J0 → **A** (go), sinon
**B** strict (OUT — éviter 5ᵉ holdover).

---

## 4. Synthèse décisions atelier

| Story | Décision J0 | Action requise |
|---|---|---|
| US-099 | ✅ go | Aucune |
| US-100 | ✅ go | Tech Lead vérifie `EmploymentPeriodRepository` interface Domain |
| US-101 | ✅ go | Tech Lead vérifie `InvoiceCreatedEvent` + `InvoicePaidEvent` Invoice BC |
| US-102 | ✅ go | Ordre exécution US-099 → US-102 figé |
| AUDIT-DAILY-HOURS | ✅ go | Tech Lead confirme accès BDD prod read-only + plan correction admin |
| **US-103** | 🟡 **risk flagged** | **Slack webhook URL configuré prod J0** OU livraison partielle (B) |
| **OPS-DECISION-B sub-epic B holdover** | ⏳ **décision atelier** | **A go si tous credentials J0** OU **B Out backlog si manquant** |

---

## 5. Métrique sprint-021

Cible runbook OPS-PREP-J0 sprint-020 retro :
- **0 holdover OPS sub-epic** sprint-021 fin

Pour atteindre cible :
- US-103 → décision A ou B (pas C qui = perte capacité)
- OPS-DECISION-B → A (go) OU B (OUT) — pas de demi-mesure

Si une story **risk flagged** se transforme en holdover post-sprint-021 →
métrique loupée → root cause analysis sprint-021 retro obligatoire.

---

## 6. Output atelier (à acter PO + Tech Lead)

### Décisions à prendre J0 atelier (2026-05-10)

| ID | Décision attendue |
|---|---|
| AT-1 | US-103 : A (webhook prod J0) / B (delivery partiel staging) / C (OUT) ? |
| AT-2 | OPS-DECISION-B : A (go +0.5 pt) / B (OUT 4ᵉ holdover) ? |
| AT-3 | Tech Lead vérification dépendances : `EmploymentPeriodRepository` + `InvoiceCreatedEvent` + `InvoicePaidEvent` + accès BDD prod read-only |
| AT-4 | Plan correction admin AUDIT-DAILY-HOURS (qui exécute correction post-output ?) |

### Mise à jour sprint-021 sprint-goal post-atelier

Selon AT-1 + AT-2 :
- Si US-103 = C → reallocate 2-3 pts capacité libre (TEST-COVERAGE-011 ou autre)
- Si OPS-DECISION-B = B → drop sub-epic D OPS-DECISION-B (rituel = 0 pts) + capacité 17 pts ferme inchangée
- Si OPS-DECISION-B = A → +0.5 pt sub-epic B sprint-021 (Slack webhook OPS)

---

## 7. Liens

- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- Sprint-020 retro action A-2 : `../sprint-020-epic-003-phase-2-acl/sprint-retro.md`
- ADR-0016 EPIC-003 Phase 3 décisions : `../../../docs/02-architecture/adr/0016-epic-003-phase-3-decisions.md`
- Sprint-021 sprint-goal : `sprint-goal.md`
- Atelier prep PO Phase 3 : `../sprint-020-epic-003-phase-2-acl/atelier-po-phase-3-prep.md`
- US-094 SlackAlertingService (réutilisé US-103) : `../../backlog/user-stories/OPS.md`

---

**Auteur** : Tech Lead
**Date prep** : 2026-05-10
**Version** : 1.0.0
**Sprint** : 021 J-2 PRE-1
