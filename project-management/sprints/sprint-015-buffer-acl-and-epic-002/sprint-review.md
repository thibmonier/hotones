# Sprint Review — Sprint 015

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 015 — Buffer ACL Promotion + EPIC-002 Kickoff |
| Date | 2026-05-07 |
| Sprint Goal | Livrer buffer Contributor + Vacation ACL + escalator step 5 + EPIC-002 kickoff |
| Capacité | 17 pts |
| Engagement | 11 pts ferme + 6 pts libre EPIC-002 |
| Livré | **11 pts (100 %)** |

---

## 🎯 Sprint Goal — Atteint ✅

**Goal :** « Livrer enfin le buffer Contributor + Vacation ACL (héritage 4
sprints), compléter l'escalator coverage à step 5/5 (40 → 45 %), et
démarrer EPIC-002 via atelier PO + premières user stories. »

**Résultat :**
- Contributor BC Phase 1 + Phase 2 ACL livrés (4 pts)
- Vacation ACL story découverte **obsolète** (BC déjà natif DDD pur) → 4 pts
  réalloués vers coverage Vacation Application + EPIC-002 brief
- Escalator step 5 final livré (40 → 45 %)
- EPIC-002 brief écrit (atelier PO sprint-016 requis pour finaliser)

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| DDD-PHASE2-CONTRIBUTOR-ACL | 4 | #182 | ✅ mergée |
| Vacation Application coverage (réalloué) | 2 | #183 | ✅ mergée |
| TEST-COVERAGE-005 escalator step 5 | 2 | #183 | ✅ mergée |
| EPIC-002-KICKOFF brief (réalloué) | 1 + 2 | #183 | ✅ mergée |
| **Total** | **11** | | **11/11 (100 %)** |

### Bonus hors-sprint absorbés

| Story | PR |
|---|---|
| Sprint-014 closure docs + sprint-015 kickoff | #179 |
| US-087 résiduel CS-Fixer + composer update | #180 |
| US-087 résiduel entrypoints.json mock | #181 |

---

## 📈 Métriques

### Tests Unit progression

| Métrique | Avant sprint-015 | Après sprint-015 |
|---|---:|---:|
| Tests Unit total | 784 | **824** (+40) |
| Tests Domain (BCs) | 274 | 274 + 7 events tests = **281** |
| Tests Application (Vacation Cmd) | 4 (Cancel only) | **10** (+6) |
| Tests Domain Contributor (nouveau BC) | 0 | **27** |

### EPIC-001 Strangler Fig — État final

| BC | Phase 1 | Phase 2 ACL | Phase 3 Controller | Phase 4 Décom |
|---|---|---|---|---|
| Vacation | ✅ natif DDD pur | n/a (pas de flat) | n/a | n/a |
| Client | ✅ | ✅ | ✅ | ✅ |
| Project | ✅ | ✅ | ✅ | ✅ |
| Order | ✅ | ✅ | ✅ | ✅ |
| Invoice | ✅ | ✅ | ✅ | ✅ |
| **Contributor** | ✅ sprint-015 | ✅ sprint-015 | n/a (pas de controller dédié) | n/a |

**EPIC-001 = COMPLET** (5 BCs ACL bridges + 1 BC natif DDD = 6 bounded contexts).

### EPIC-002 préparation

- Brief écrit (5 user stories candidates US-091..US-095, 19 pts macro)
- Plan 3 sprints (016/017/018)
- Atelier PO sprint-016 J1 requis (budget, stack, KPIs, cold start, scope smoke test)

### Vélocité (8 derniers sprints)

| Sprint | Engagé | Livré |
|---|---:|---:|
| 008 | 26 | 26 |
| 009 | 22 | 22 |
| 010 | 18 | 18 |
| 011 | 14 | 14 |
| 012 | 15 | 15 |
| 013 | 11 | 11 |
| 014 | 16 | 16 |
| **015** | **11** | **11** |

Cumul 8 sprints : **133 pts livrés**. Vélocité moyenne : **16,6 pts/sprint**.

---

## 🎬 Démonstration

### Contributor BC complet (PR #182)

- 7 fichiers Domain (Aggregate + 3 VOs + 1 event + 1 exception + interface)
- 3 fichiers Infrastructure (2 translators + ACL repo)
- 27 tests Unit Domain (ContributorIdTest, PersonNameTest, ContractStatusTest,
  ContributorTest, etc.)
- Service alias wired dans `config/services.yaml`

### Découverte Vacation natif DDD (story obsolète)

Investigation PR #182 : `src/Domain/Vacation/` complet avec
`DoctrineVacationRepository extends ServiceEntityRepository` directement.
Pas de flat entity `App\Entity\Vacation`. Pas d'ACL bridge nécessaire.

→ Story DDD-PHASE2-VACATION-ACL non actionnable. 4 pts redirigés.

### Vacation Application coverage (PR #183)

6 nouveaux tests :
- `RequestVacationHandlerTest` (3) : persist + dispatch notification
- `ApproveVacationHandlerTest` (1) : approve + notification 'approved'
- `RejectVacationHandlerTest` (2) : reject avec/sans rejectionReason

### Coverage step 5 escalator final (PR #183)

7 tests Domain Events (Client/Project/Order/Invoice). **Escalator finished**
(plan 5 steps livré : 25 → 30 → 35 → 40 → 45 %).

### EPIC-002 brief (PR #183)

`project-management/backlog/epics/EPIC-002-observability-and-performance.md` :
- Contexte (post-mortem US-090 + sprint-014 retro S-1)
- Objectifs business
- Critères MMF (p95 < 800ms, alerting < 5min, dashboard 5 KPIs)
- 5 US candidates US-091..US-095 (19 pts)
- Plan macro 3 sprints
- Atelier PO sprint-016 : 5 questions à arbitrer

---

## 💬 Feedback PO / Stakeholders

### Positif

- **Promesse buffer 4 sprints honorée** : Contributor BC livré (engagement
  irrévocable retro sprint-014).
- **Découverte Vacation obsolète proactive** : 4 pts réalloués valorisent
  le sprint plutôt que faire du faux travail.
- **Escalator coverage 5/5 atteint** : plan livré complet sans dérive.
- **EPIC-002 brief structuré** : atelier PO encadré (5 questions ciblées).

### À améliorer

- **Story Vacation ACL aurait dû être détectée obsolète sprint-011 retro** :
  héritée 4 sprints sans audit Phase 1 actuelle. Process : audit BC état
  réel quand story buffer héritée > 2 sprints.
- **Tests E2E Contributor BC pas écrits** sprint-015. Pattern connu (Client/Project/Order/Invoice
  tous ont E2E DddTest). Sprint-016 ?

### Nouvelles demandes

EPIC-002 atelier sprint-016 — 5 questions PO à préparer :
1. Budget mensuel acceptable observabilité ?
2. Stack : Sentry vs Datadog vs OTel native ?
3. 5 KPIs business essentiels ?
4. Cold start résolution : starter Render ou keep-alive ?
5. Scope smoke test post-deploy ?

---

## 📅 Prochaines étapes — Sprint 016

| Story candidate | Pts | Priorité |
|---|---:|---|
| EPIC-002-KICKOFF-WORKSHOP atelier PO J1 (1h) | 1 | Must |
| US-091 OpenTelemetry tracing | 5 | Must (post-atelier) |
| US-092 Smoke test post-deploy GH Action | 3 | Must |
| TEST-CONTRIBUTOR-E2E (rattrapage) | 2 | Should |

**Engagement cible : 11 pts**.

Cf. `sprint-016-epic-002-observability/sprint-goal.md`.
