# Sprint Retrospective — Sprint 023

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 023 — EPIC-003 Phase 3 Finition + Coverage Step 12 |
| Date | 2026-05-11 (clôture anticipée — sprint J0..J0+1 compactés) |
| Format | Starfish |
| Engagement | 12 pts ferme |
| Livré | 12 pts (100 %) |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Recalibrage 12 pts ferme confirmé 3ᵉ sprint consécutif** (sprint-021 exception 117 % → sprint-022 108 % → sprint-023 100 %). Vélocité durable. Pattern de pilotage stabilisé. |
| K-2 | **0 holdover OPS sub-epic 3ᵉ sprint consécutif** — cible runbook OPS-PREP-J0 atteinte. ADR-0017 Sub-epic B Out Backlog reste valide. Pattern crédible. |
| K-3 | **Strangler fig pattern complet EPIC-003 Phase 3** : 3 sprints (022 dispatch dual → 023 consume direct → suppression legacy `LowMarginAlertEvent`). Réplication propre EPIC-001 (sprint-008..014). Pattern maîtrisé sprints futurs. |
| K-4 | **TDD strict respecté sur stories complexes** : US-106 refactor NotificationSubscriber + tests régression notifications. US-107 persistence + tests migration up/down. US-108 hiérarchie + tests UC `CalculateProjectMargin` chain résolution. |
| K-5 | **0 commit `--no-verify` sprint-023** — discipline maintenue. Pre-push hook Docker check passé sur tous commits (US-106..US-108 + COVERAGE-012 + INTEGRATION-21-SUITE). |
| K-6 | **BUFFER Integration tests sprint-021 100 % livré** (4/4 composants : sprint-022 livré DoctrineEmploymentPeriodAdapter + AUDIT-DAILY-HOURS, sprint-023 livré WeeklyTimesheetController + Workflow E2E). Dette tests HTTP/E2E sprint-021 soldée. |
| K-7 | **Coverage step 12 livré pattern pragmatic** : Order + Invoice BCs Events/Exceptions/Entity. Trajectoire +2-3 pp / sprint stable (68 → 70 %). |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Capacité libre 1-2 pts non utilisée sprint-023** : engagement ferme livré sans débordement, mais slot US-109 reserved sans contenu défini → opportunité manquée Mago cleanup ou Phase 4 anticipation. | Sprint-024 J-2 atelier : pré-allouer cap libre à story concrète (pas slot vide). |
| L-2 | **Render image stale 2026-01-12 — 6ᵉ sprint consécutif** holdover PRE-5 user-tracked. Smoke prod red chronique. | **Signal d'arrêt** — sprint-024 décision PO obligatoire : redeploy manuel OU épargne cap libre dédiée OU ADR Out Backlog (pattern ADR-0017). |
| L-3 | **Mago lint 626 errors stable** depuis sprint-021 (627 → 626). Dette code legacy non attaquée. | Sprint-024+ batch dédié Mago cleanup (héritage A-6 sprint-022 retro). Estimation 3-5 pts selon batch sélection. |
| L-4 | **Deptrac 192 violations + 43 skipped + 1 error VacationRepository** dette architecture. | Audit ciblé sprint-024+ : VacationRepository → Domain pure aggregate (rattrapage EPIC-001 Phase 4 résidu). |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Atelier OPS-PREP-J0 J-2 sprint-024 OBLIGATOIRE** — 3ᵉ sprint consécutif application stricte runbook = pattern validé. Maintenir J-2 plutôt que J0 atelier. | Préserve la qualité de la décision matrix Sub-epic. |
| S-2 | **Pré-allocation explicite cap libre sprint-024 J-2** : éviter slot vide US-XXX reserved. Liste candidates (Mago cleanup batch, Phase 4 anticipation US-110/111, audit VacationRepository, redeploy Render PRE-5). | Évite gâchis cap libre constatée sprint-023. |
| S-3 | **EPIC-003 Phase 4 kickoff sprint-024** : KPIs DSO + temps facturation + adoption marge (US-110..US-112). Audit data migration legacy `WorkItem.cost` (US-113). | MVP EPIC-003 livré + finition Phase 3 close → Phase 4 KPIs business délivrables PO + clôture EPIC-003 sprint-026 cible. |
| S-4 | **Décision PO PRE-5 Render redeploy** sprint-024 J-2 : 6ᵉ sprint consécutif holdover = trigger réversibilité. Suit pattern ADR-0017 (Out Backlog avec triggers replan documentés). | Sortir du backlog implicite. Décision structurelle traçable. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Slot US-XXX reserved sans contenu** dans backlog sprint (sprint-023 US-109). Capacité libre doit pointer story concrète pré-allouée. | Évite gâchis cap libre. Pré-allocation explicite obligatoire J-2 ou Planning P1. |
| ST-2 | **Holdover OPS user-tracked > 5 sprints sans décision structurelle**. Render PRE-5 = 6ᵉ sprint = action obligatoire sprint-024. | Pattern ADR-0017 répliqué : Out Backlog ou Replan dédié, pas implicit holdover. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Sprint compactés J0..J0+1** : sprint-022 + sprint-023 clôturés J0+1 (kickoff sprint-N+1 lendemain). Pattern viable engagement 12 pts ferme. À considérer sprint-024 si scope figé tôt. | Vélocité élevée + livraison continue. |
| M-2 | **Persistence snapshot pattern (US-107)** : transient compute → persistent + invalidation event-driven. Pattern réplicable sprint-024 KPI DSO + temps facturation (cf Phase 4). | Perf p95 dashboard < 800ms cible EPIC-002. |
| M-3 | **Configurabilité hiérarchique pattern (US-108)** : global → Client → Project. Réplicable sprint-024 Phase 4 KPIs (override par client B2B). | Flexibilité B2B sans complexity client-side. |

---

## 🎯 Actions concrètes Sprint 024

| ID | Action | Owner | Deadline | Priorité |
|---|---|---|---|---|
| A-1 | Atelier OPS-PREP-J0 J-2 sprint-024 (runbook §2 + décision matrix §3) | PO + Tech Lead | Sprint-024 J-2 | Must |
| A-2 | Mesurer coverage actuel post sprint-023 (CI report) → cible 70 % vérifiée | Tech Lead | Sprint-024 J-1 | Must |
| A-3 | Décision PO scope sprint-024 (Phase 4 kickoff US-110/111/112 OU Mago cleanup batch dédié OU sprint OPS replan) | PO | Sprint-024 Planning P1 | Must |
| A-4 | Décision PO PRE-5 Render redeploy : exécution OU ADR Out Backlog (pattern ADR-0017) | PO + user | Sprint-024 J-2 | Must |
| A-5 | Stories sprint-024 spécifiées 3C + Gherkin (US-110..US-113 si Phase 4) | PO | Sprint-024 J0 fin | Must |
| A-6 | Maintenir baseline 12 pts ferme (4ᵉ confirmation) | PO + Tech Lead | Sprint-024 P1 | Must |
| A-7 | Pré-allocation cap libre 1-2 pts explicite (vs slot reserved vide) | PO | Sprint-024 P1 | Must |
| A-8 | Audit ciblé VacationRepository Deptrac violation Domain pure | Tech Lead | Sprint-024+ | Should |

---

## 🔄 Directive Fondamentale Rétrospective

> « Quels que soient les enseignements de cette rétrospective, nous comprenons
> et croyons sincèrement que chacun a fait du mieux qu'il pouvait avec les
> ressources, les informations, les compétences et le temps dont il disposait. »

— Norm Kerth, *Project Retrospectives*

---

## 📊 Indicateurs cible sprint-024

| Indicateur | Cible | Statut sprint-023 |
|---|---|---|
| Engagement ferme | 12 pts (4ᵉ confirmation) | ✅ 100 % livré |
| Holdover OPS | 0 (4ᵉ sprint consécutif) | ✅ 0 sprint-023 |
| Commits `--no-verify` | 0 | ✅ 0 sprint-023 |
| Coverage progression | +2-3 pp (70 → 72-73 %) | ✅ 70 % atteint |
| Mago errors | < 626 (batch cleanup démarré) | ⚠️ stable |
| Cap libre allocation | Story concrète pré-allouée | ⚠️ slot vide sprint-023 |
| PRE-5 Render redeploy | Décision structurelle prise | ❌ 6ᵉ sprint holdover |
| EPIC-003 Phase 4 kickoff | US-110 démarré | ⏳ planifié |

---

## 🔗 Liens

- Sprint-023 review : `sprint-review.md`
- Sprint-022 retro : `../sprint-022-epic-003-phase-3-completion/sprint-retro.md`
- ADR-0013 / 0015 / 0016 / 0017 EPIC-003
- Runbook OPS-PREP-J0 : `../../../docs/runbooks/sprint-ops-prep-j0.md`
- EPIC-003 file : `../../backlog/epics/EPIC-003-workitem-and-profitability.md`
