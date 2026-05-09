# Sprint Review — Sprint 019

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 019 — EPIC-003 WorkItem & Profitability kickoff + EPIC-002 stragglers |
| Date | 2026-05-09 (clôture anticipée — sprint J2) |
| Sprint Goal | EPIC-003 Phase 1 démarrage + stragglers EPIC-002 OPS + coverage 55→62 % + dette env Docker/APCu |
| Capacité | 13 pts |
| Engagement ferme | 12 pts |
| Capacité libre | 1 pt |
| Livré | **11 pts (92 % engagement ferme)** + 1 pt holdover OPS Sub-epic B |

---

## 🎯 Sprint Goal — Atteint partiellement ✅

**Goal :** « Démarrer EPIC-003 WorkItem & Profitability (Phase 1 DDD entity +
audit données existantes), finir stragglers EPIC-002 (OPS Slack + Sentry alert
rules + SMOKE activation), pousser escalator coverage 55 → 60 % via
Order/Project aggregates Domain, nettoyer dette environnement (alternative
Docker Mac + APCu local). »

**Résultat :**
- ✅ EPIC-003 Phase 1 : audit + DDD WorkItem aggregate + VOs + Events livrés
- ⏳ **Sub-epic B OPS reporté sprint-020** : Slack webhook + Sentry alerts +
  SMOKE config nécessitent credentials côté user (1 pt holdover)
- ✅ Coverage push 55 → 62 % via 3 PRs (Invoice sprint-018, Order sprint-019, Project sprint-019)
- ✅ Dette environnement : ADR-0014 OrbStack recommandé + Option APCu pecl

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| AUDIT-WORKITEM-DATA (sub-epic A) | 1 | #199 | ✅ mergée |
| US-097 EPIC-003 Phase 1 DDD WorkItem (sub-epic A) | 3 | #200 | ✅ mergée |
| TEST-COVERAGE-008 OrderLine + OrderSection (sub-epic C) | 2 | #201 | ✅ mergée |
| TEST-COVERAGE-009 Project Aggregate extensions (sub-epic C) | 2 | #202 | ✅ mergée |
| ENV-DOCKER-ALTERNATIVE + ENV-APCU-LOCAL (sub-epic D) | 3 | #203 | ✅ mergée |
| **Total** | **11** | | **11/12 ferme = 92 %** |

### Reporté sprint-020

| Story | Pts | Raison |
|---|---:|---|
| US-094-OPS Slack webhook + Sentry alerts (sub-epic B) | 0.5 | OPS manuel — credentials user |
| SMOKE-OPS user smoke + GH secrets/var (sub-epic B) | 0.5 | OPS manuel — credentials user |
| Capacité libre (TEST-COVERAGE-010 OU buffer) | 1 | Cap libre non consommée — sprint productif tel quel |

---

## 📈 Métriques

### EPIC-003 Phase 1 démarrée

**Audit data** (PR #199) — 7 risks identifiés :
- Q3 critique : `cjm`/`tjm` doubles nullable → coût 0 silencieux
- Q4 élevé : rate non figé date timesheet (recalcul historique diverge)
- Q1/Q2/Q5/Q6/Q7 (faible à moyenne)

**DDD WorkItem aggregate** (PR #200) — Domain pur livré :
- 3 VOs (`WorkItemId`, `WorkedHours`, `HourlyRate`) — non-null par construction
- Aggregate Root `WorkItem` avec rates **figés création** (mitigation Q4)
- Events `WorkItemRecordedEvent` + `WorkItemRevisedEvent`
- Repository interface (impl Phase 2 sprint-020)
- 47 tests Unit / 81 assertions

### Coverage escalator

| Step | Cible | Atteint | Tests ajoutés |
|---|---:|---|---|
| Step 7 (sprint-018) | 55 % | ✅ | InvoiceTest 30 tests |
| Step 8 (sprint-019) | 60 % | ✅ | OrderLineTest 15 + OrderSectionTest 15 = 30 tests |
| **Step 9 (sprint-019)** | **62 %** | ✅ | ProjectTest +20 tests (33 total) |

### Dette environnement adressée

- **ADR-0014** : OrbStack recommandé Mac (vs Docker Desktop) — drop-in
  compose, démarrage 2s vs 30s, perfs I/O ~10x natifs Mac FS
- **Fallback Colima** documenté si contrainte license OSS
- **APCu pecl Option A** (install) + **Option B fallback** (`--no-verify` doc)

---

## 🚀 Vélocité 10 derniers sprints

| Sprint | Engagement ferme | Livré | % |
|---|---:|---:|---:|
| 010 | 13 | 13 | 100 % |
| 011 | 13 | 13 | 100 % |
| 012 | 13 | 13 | 100 % |
| 013 | 9 | 9 | 100 % |
| 014 | 12 | 12 | 100 % |
| 015 | 13 | 13 | 100 % |
| 016 | 11 | 11 | 100 % |
| 017 | 10 | 13 | 130 % |
| 018 | 8.5 | 10.5 | 124 % |
| **019** | **12** | **11** | **92 %** |

Vélocité moyenne 10 sprints : ~12 pts. Sprint-019 = 11 pts livrés (sub-epic
B OPS holdover sprint-020 = 1 pt). **Pattern recalibré atteint** : engagement
12 pts ferme proche réalité livrée (vs sprints 017-018 explosifs 130%/124 %).

---

## 🎯 Démonstration

### EPIC-003 Phase 1 livré
1. Audit data prod (`docs/02-architecture/epic-003-audit-existing-data.md`)
2. ADR-0013 décisions PO scope/MVP/timeline/stack/KPIs
3. DDD WorkItem aggregate Domain pur (VOs + Aggregate + Events + Repository interface)

### Coverage 62 %
- Domain coverage exhaustive sur 3 Aggregate Roots majeurs (Invoice, Order, Project)
- Tests Unit pure host PHP (sans Docker) → architecture hexagonale validée

### Dette environnement
- ADR-0014 OrbStack publié, migration step-by-step ready côté dev

---

## 💬 Feedback PO (à recueillir)

Questions atelier sprint-020 J1 :
1. Décisions PO Phase 2 (informe sprint-020 US-098) :
   - Timesheets `task = NULL` → exclus marge OU allocation fictive niveau projet ?
   - Doublons `(contributor, date, task)` → dédup OU toléré (Risk Q6 audit) ?
2. Sub-epic B OPS sprint-019 holdover : timing config Slack webhook + Sentry alerts + SMOKE ?
3. Capacité libre sprint-020 : prioriser EPIC-003 Phase 2+ OU TEST-COVERAGE-010 step 10 ?

---

## 🔗 Liens

- PR #199 — Audit data EPIC-003
- PR #200 — US-097 DDD WorkItem Phase 1
- PR #201 — TEST-COVERAGE-008 Order
- PR #202 — TEST-COVERAGE-009 Project
- PR #203 — Sub-epic D OrbStack + APCu
- ADR-0013 — EPIC-003 scope WorkItem & Profitability
- ADR-0014 — OrbStack Mac recommandation
