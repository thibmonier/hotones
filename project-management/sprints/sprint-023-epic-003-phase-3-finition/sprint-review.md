# Sprint Review — Sprint 023

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 023 — EPIC-003 Phase 3 Finition + Coverage Step 12 |
| Date clôture | 2026-05-11 (clôture anticipée — sprint J0..J0+1 compactés, comme sprint-022) |
| Sprint Goal | Refactor `NotificationSubscriber` Domain Events directement + suppression `LowMarginAlertEvent` legacy + BUFFER tests Integration sprint-021 suite + Coverage step 12 (68 → 70 %) + 0 holdover OPS 3ᵉ sprint consécutif |
| Capacité | 12 pts ferme + 1-2 pts libre |
| Engagement ferme | 12 pts |
| Capacité libre | 0 pt utilisé (US-109 reserved non démarré) |
| Livré | **12 pts (100 % capacité ferme)** |

---

## 🎯 Sprint Goal — Atteint intégralement ✅

**Goal** : « EPIC-003 Phase 3 finition : refactor `NotificationSubscriber`
Domain Events directement + suppression `LowMarginAlertEvent` legacy
(sprint-022 US-105 strangler fig completion). BUFFER tests Integration
sprint-021 suite (WeeklyTimesheetController Functional + Workflow E2E).
Coverage step 12 (68 → 70 %). Application stricte runbook OPS-PREP-J0 J-2
→ 0 holdover OPS 3ᵉ sprint consécutif. »

**Résultat 100 %** :
- ✅ Refactor `NotificationSubscriber` consume `MarginThresholdExceededEvent` Domain Event directement
- ✅ Suppression `LowMarginAlertEvent` legacy du codebase + drop dual dispatch `AlertDetectionService`
- ✅ Persistence `Project.margin` snapshot (cols `cout_total_cents` + `facture_total_cents` + `marge_calculated_at`)
- ✅ Configurabilité hiérarchique seuil marge ADR-0016 Q5.1 D (default global → Client override → Project override)
- ✅ Coverage step 12 (68 → 70 %) via Order + Invoice BCs Events / Exceptions / Entity
- ✅ BUFFER Integration tests sprint-021 100 % livré (WeeklyTimesheetController Functional + Workflow E2E cross-aggregate)
- ✅ **0 holdover OPS sub-epic** — 3ᵉ sprint consécutif (cible runbook OPS-PREP-J0 atteinte)
- ✅ 0 commit `--no-verify` sprint-023

---

## 📦 User Stories Livrées

| Story | Pts | Commit | Sub-epic |
|---|---:|---|---|
| US-106 Refactor `NotificationSubscriber` Domain Events + `LowMarginAlertEvent` removal | 3 | ade9f839 (PR #231) | A |
| US-107 Persistence margin snapshot Project | 3 | 358b2107 | D |
| US-108 Configurabilité hiérarchique seuil marge Q5.1 D | 2 | 1d23762a | E |
| TEST-INTEGRATION-21-SUITE BUFFER `WeeklyTimesheetController` Functional + Workflow E2E | 2 | 74f8a880 (PR #232) | B |
| TEST-COVERAGE-012 Order + Invoice BCs Events / Exceptions / Entity | 2 | 31db7c1b (PR #233) | C |
| **Total** | **12** | | **100 % engagement ferme** |

### Non démarré

| Story | Pts | Statut | Raison |
|---|---:|---|---|
| US-109 (slot cap libre) | — | reserved | Engagement ferme 12 pts livré sans débordement. Cap libre non sollicitée. Décision PO sprint-024 J-2 : reporter sur Phase 4 (US-110..US-113) ou Mago cleanup A-6. |

---

## 📈 Métriques

### Engagement vs livré

| Sprint | Capacité ferme | Engagement | Livré | Ratio | Tendance |
|---|---:|---:|---:|---:|---|
| sprint-019 | 12 | 12 | TBD | TBD | — |
| sprint-020 | TBD | TBD | TBD | TBD | — |
| sprint-021 | TBD | TBD | TBD | 117 % | dépassement contrôlé |
| sprint-022 | 12 | 12 | 13 | 108 % | recalibrage acté |
| **sprint-023** | **12** | **12** | **12** | **100 %** | **stabilisation** |

**Vélocité moyenne 13 sprints récents** : ~11 pts (sprint-022 ST-1 acté).
**Recalibrage durable 12 pts ferme** = 3ᵉ confirmation (sprint-021 retro
exception → sprint-022 validation → sprint-023 confirmation).

### Coverage

| Sprint | Step | Cible | Atteint |
|---|---|---:|---:|
| sprint-021 | 10 | 62 → 65 % | ✅ |
| sprint-022 | 11 | 65 → 68 % | ✅ (Vacation + Contributor BCs) |
| **sprint-023** | **12** | **68 → 70 %** | **✅ (Order + Invoice BCs)** |

### Qualité code

| Gate | Sprint-022 | Sprint-023 | Évolution |
|---|---|---|---|
| PHPStan max | 0 erreur ✅ | 0 erreur ✅ | stable |
| PHP CS Fixer | 0/835 files | 0/835 files ✅ | stable |
| Tests pre-push | 1431 ✅ | 1445 ✅ | +14 tests (coverage step 12) |
| Mago lint | 627 errors | 626 errors | -1 (drift mineur) |
| Deptrac violations | TBD | 192 | dette stable EPIC-003 |
| Commits `--no-verify` | 0 ✅ | 0 ✅ | maintenu |

### OPS sub-epic

- **3ᵉ sprint consécutif sans holdover OPS** (sprint-021 + 022 + 023) — cible runbook OPS-PREP-J0 atteinte
- ADR-0017 Sub-epic B Out Backlog acté sprint-022 confirme efficacité du pattern

---

## 🚀 Démos clés

### 1. Strangler fig completion EPIC-003 Phase 3

- Avant sprint-022 : `LowMarginAlertEvent` legacy seul, dispatched depuis `AlertDetectionService`.
- Sprint-022 US-105 : dual dispatch ajouté (legacy + `MarginThresholdExceededEvent` Domain Event), `LowMarginAlertEvent` `@deprecated`.
- **Sprint-023 US-106** : `NotificationSubscriber` consume Domain Event directement, `LowMarginAlertEvent` supprimé du codebase, dual dispatch retiré.

Pattern strangler fig EPIC-001 (sprint-008..014) répliqué proprement sur EPIC-003 (3 sprints : 022 dispatch dual → 023 consume direct → suppression legacy).

### 2. Persistence margin snapshot

- Avant : `Project.getMargin()` calculé runtime (transient, recalcul à chaque appel).
- **Sprint-023 US-107** : snapshot persisté via cols `cout_total_cents` + `facture_total_cents` + `marge_calculated_at` + invalidation event-driven.
- Bénéfice perf p95 dashboard (cf EPIC-002 cible < 800ms).

### 3. Configurabilité hiérarchique seuil marge

- ADR-0016 Q5.1 D : seuil marge configurable au niveau hiérarchique.
- **Sprint-023 US-108** : default global → override Client → override Project. UC `CalculateProjectMargin` résout la chaîne de précédence.
- PO peut piloter alerting par client (B2B) ou projet (one-shot exceptionnels).

### 4. Coverage step 12 — Order + Invoice BCs

- Pattern pragmatic répliqué (sprint-022 Vacation + Contributor) : Events + Exceptions + Entity extensions.
- Coverage prod 70 % atteint. Trajectoire stable +2-3 pp par sprint.

---

## 🔗 PRs mergées sprint-023

| PR | Story | Description courte |
|---|---|---|
| #231 | US-106 | Refactor NotificationSubscriber Domain Events |
| #232 | TEST-INTEGRATION-21-SUITE | BUFFER WeeklyTimesheet + Workflow E2E |
| #233 | TEST-COVERAGE-012 | Order + Invoice BCs Events/Exceptions/Entity |
| (direct) | US-107 | Persistence margin snapshot |
| (direct) | US-108 | Configurabilité hiérarchique seuil marge |

---

## ⚠️ Issues hors sprint persistantes

| Issue | Sprint affecté | Action sprint-024 |
|---|---|---|
| **Render image stale 2026-01-12** : `/health` raw octet-stream | 6ᵉ sprint consécutif holdover | **Signal d'arrêt** — décision PO sprint-024 : redeploy manuel OBLIGATOIRE OU épargner cap libre dédiée |
| **Sub-epic B OPS** ADR-0017 Out Backlog | Replan dédié | Hors sprint-024. Replan quand owner aligné + 4 credentials simultanés |
| **Mago lint** 626 errors stable | Sprint-024+ batch dédié | Action retro A-6 sprint-023 — évaluer cleanup batch sprint-024+ |

---

## 📊 État EPIC-003

| Phase | Sprints | Statut | Stories |
|---|---|---|---|
| Phase 1 — Foundation DDD | sprint-019 | ✅ | US-097 + AUDIT |
| Phase 2 — ACL + invariants | sprint-020 | ✅ | US-098 + US-099 + ADR-0015 |
| Phase 3.1 — UC + Workflow + UI | sprint-021 | ✅ | US-100..US-102 + ADR-0016 |
| Phase 3.2 — MarginCalculator + alerte | sprint-022 | ✅ | US-103..US-105 + ADR-0017 |
| **Phase 3.3 — Finition strangler + persistence** | **sprint-023** | **✅** | **US-106..US-108** |
| Phase 4 — KPIs dashboard + migration | sprint-024+ | ⏳ planifié | US-110..US-113 |

**MVP EPIC-003 livré sprint-022 (US-103 + US-104) + finition sprint-023.**
Trigger abandon ADR-0013 cas 1 (> 6 sprints sans MVP) NON déclenché — MVP
en 4 sprints, finition phase 3 en 5ᵉ sprint.

---

## 🔗 Liens

- Sprint-022 retro : `../sprint-022-epic-003-phase-3-completion/sprint-retro.md`
- ADR-0013 — EPIC-003 scope
- ADR-0016 — Phase 3 décisions (UC RecordWorkItem + Workflow + UI + Q5.1 hierarchie)
- ADR-0017 — Sub-epic B Out Backlog
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- EPIC-003 file : `../../backlog/epics/EPIC-003-workitem-and-profitability.md`
