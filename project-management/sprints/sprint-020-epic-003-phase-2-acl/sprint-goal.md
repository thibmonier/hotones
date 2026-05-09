# Sprint 020 — EPIC-003 Phase 2 ACL + OPS holdover

| Champ | Valeur |
|---|---|
| Numéro | 020 |
| Début | 2026-06-19 |
| Fin | 2026-07-03 |
| Durée | 10 jours ouvrés |
| Capacité | 12 pts (recalibrage post sprint-019 92 %) |
| Engagement ferme | **10 pts** + 2 pts capacité libre |

---

## 🎯 Sprint Goal

> « EPIC-003 Phase 2 ACL : translators flat↔DDD + DoctrineDddWorkItemRepository
> pour mettre WorkItem en production lecture/écriture. Finir OPS holdover
> sprint-019 (Slack + Sentry alerts + SMOKE config). Pousser escalator
> coverage 62 → 65 % via ValueObjects partials. »

**Atelier PO Phase 2 J1** : décisions sur questions audit héritage (task=NULL
exclus marge ? doublons dédup ?). ADR-0015 si nécessaire.

---

## Backlog engagé (10 pts)

### Sub-epic A — EPIC-003 Phase 2 ACL (5 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| ATELIER-PHASE-2 | Atelier PO J1 — décisions Q1 (task=NULL exclus marge?) + Q6 (doublons dédup?) + ADR-0015 si nécessaire | 1 | Sprint-019 retro A-3 + S-3 |
| US-098 | EPIC-003 Phase 2 ACL — `WorkItemFlatToDddTranslator` + `WorkItemDddToFlatTranslator` + `DoctrineDddWorkItemRepository` (impl Repository interface livrée Phase 1) | 4 | Pattern sprints 008-013 strangler fig |

### Sub-epic B — OPS holdover sprint-019 (1 pt)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| US-094-OPS | Slack webhook Render prod + staging + Sentry alert rules `#alerts-prod` | 0.5 | Héritage sprint-017+018+019 |
| SMOKE-OPS | User smoke prod + GH secrets `SMOKE_USER_EMAIL/PASSWORD` + GH var `SMOKE_EXTENDED_ENABLED=true` + premier run validation | 0.5 | Héritage sprint-018+019 |

### Sub-epic C — Coverage escalator (2 pts)

| ID | Titre | Pts | Notes |
|---|---:|---:|---|
| TEST-COVERAGE-010 | Step 10 : push coverage 62 → 65 % via Domain ValueObjects partials (Money, Email, Address) | 2 | Sprint-018 retro S-3 héritage targeting Aggregate Roots → étendre ValueObjects |

### Sub-epic D — Pre-Phase-2 audit (2 pts)

| ID | Titre | Pts | Notes |
|---|---|---:|---|
| AUDIT-CONTRIBUTORS-CJM | Script SQL audit Contributors sans CJM en prod (Risk Q3) → output liste + correction admin avant Phase 2 | 1 | Sprint-019 retro A-7 héritage. Pré-requis bloquant US-098 deploy. |
| OPS-PREP-J0 | Pattern OPS prep J0 documenté pour sprints futurs (sprint-019 retro S-1 + A-8) | 1 | Évite holdover récurrent OPS manuel |

---

## Capacité libre (2 pts)

À allouer J3-J5 selon avancement ferme :
- **EPIC-003 Phase 3 démarrage anticipé** (UC `RecordWorkItem` skeleton — 2 pts)
- BUFFER : OrderTest extensions Aggregate Root paths non couverts (1-2 pts)

---

## Definition of Done

- ✅ Tests Unit + Integration passent
- ✅ PHPStan max 0 erreur (CI Docker)
- ✅ CS-Fixer + Rector + Deptrac + Mago OK
- ✅ Snyk Security clean
- ✅ Smoke test post-deploy green sur Render (minimum + extended si activé)
- ✅ Documentation à jour (runbook + ADR si nouvelle décision)
- ✅ PR review validée + merge linéaire main
- ✅ **OrbStack installé côté dev** (sprint-019 retro A-1) → fin du `--no-verify` chronique
- ✅ APCu pecl installé local (sprint-019 retro A-2) → PHPStan local fonctionnel

---

## 🔗 Cérémonies

| Cérémonie | Date prévue |
|---|---|
| Sprint Planning P1 (PO scope) | 2026-06-19 09:00 |
| **Atelier PO Phase 2 décisions** (Q1 task=NULL + Q6 doublons) | **2026-06-19 J1 14:00** (~1h) |
| Sprint Planning P2 (équipe technique tasks) | 2026-06-19 16:00 |
| Daily standup | Quotidien 09:30 |
| Sprint Review | 2026-07-03 14:00 |
| Rétrospective | 2026-07-03 16:30 |

---

## 🎯 Actions héritées sprint-019 retro

| ID | Action | Owner | Statut |
|---|---|---|---|
| A-1 | Self-install OrbStack côté dev | Chaque dev | ✅ inclus DoD sprint-020 |
| A-2 | Install pecl APCu PHP 8.5 brew | Tech Lead | ✅ inclus DoD sprint-020 |
| A-3 | Atelier décisions PO Phase 2 | PO + Tech Lead | ✅ inclus sprint-020 sub-epic A |
| A-4 | Slack webhook Render prod + staging | Tech Lead | ✅ inclus sprint-020 sub-epic B |
| A-5 | Sentry alert rules → Slack | Tech Lead | ✅ inclus sprint-020 sub-epic B |
| A-6 | SMOKE OPS config | Tech Lead | ✅ inclus sprint-020 sub-epic B |
| A-7 | Script audit Contributors sans CJM | Tech Lead | ✅ inclus sprint-020 sub-epic D |
| A-8 | Pattern OPS prep J0 documenté | Tech Lead | ✅ inclus sprint-020 sub-epic D |

---

## 📊 Indicateurs cibles fin sprint

- Coverage 65 % (post sprint-019 step 9 = 62 %)
- EPIC-003 Phase 2 ACL livrée — `DoctrineDddWorkItemRepository` opérationnel
- ADR-0015 si décision Q1/Q6 PO Phase 2 prise
- Slack webhook + Sentry alerts actifs `#alerts-prod`
- SMOKE-PROD-EXTENDED en run automatique chaque deploy
- 0 commit `--no-verify` sprint-020 (validation OrbStack + APCu installés)
- Audit Contributors sans CJM exécuté + corrections admin appliquées

---

## 🔗 Liens

- Sprint-019 review : `../sprint-019-*/sprint-review.md`
- Sprint-019 retro : `../sprint-019-*/sprint-retro.md`
- ADR-0013 EPIC-003 scope
- ADR-0014 OrbStack Mac
- Audit data : `docs/02-architecture/epic-003-audit-existing-data.md`
- Local env Mac : `docs/04-development/local-environment-mac.md`
