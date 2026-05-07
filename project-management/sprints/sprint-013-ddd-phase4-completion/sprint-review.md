# Sprint Review — Sprint 013

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 013 — DDD Phase 4 Completion + Coverage Step 3 |
| Date | 2026-05-07 |
| Sprint Goal | Compléter Phase 4 (3 décommissions) + escalator coverage 30→35 % |
| Capacité | 19 pts (vélocité 5 sprints précédents) |
| Engagement | 11 pts (+ 8 pts buffer non engagé) |
| Livré | **11 pts (100 %)** |

---

## 🎯 Sprint Goal — Atteint ✅

**Goal :** « Compléter Phase 4 du strangler fig (3 décommissions Project +
Order + Invoice) + escalator coverage 35 % + buffer Vacation/Contributor
ACL si capacité. »

**Résultat :**
- 3/3 décommissions livrées dans 1 PR groupée (#165)
- Escalator step 3 livré (PR #164)
- **EPIC-001 strangler fig 4 phases COMPLÈTE**
- Buffer Vacation/Contributor non activé (capacité utilisée pour fix infra
  CI imprévu OPS-008)

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| TEST-COVERAGE-003 | 2 | #164 | ✅ mergée |
| DDD-PHASE4-DECOMMISSION-PROJECT-NEW | 3 | #165 | ✅ mergée |
| DDD-PHASE4-DECOMMISSION-ORDER-NEW | 3 | #165 | ✅ mergée |
| DDD-PHASE4-DECOMMISSION-INVOICE-NEW | 3 | #165 | ✅ mergée |
| **Total** | **11** | | **11/11 (100 %)** |

### Bonus hors-sprint (imprévu absorbé sans dépassement)

| Story | Pts | PR | Notes |
|---|---:|---|---|
| OPS-008-FIX-PR-COMMENT-GIT-CONTEXT | 0 (fix urgent) | #166 | Bug CI infra découvert sur PR #163 |
| sprint-012 closure docs (review/retro) | 0 (process) | #163 | Doc-only |

---

## 📈 Métriques

### EPIC-001 Strangler Fig

| Phase | Avant sprint-013 | Après sprint-013 |
|---|---|---|
| Phase 0 — Foundation | ✅ Mergée | ✅ Mergée |
| Phase 1 — BCs additifs (4) | ✅ 4/4 | ✅ 4/4 |
| Phase 2 — ACL (4) | ✅ 4/4 | ✅ 4/4 |
| Phase 3 — Controllers (4) | ✅ 4/4 | ✅ 4/4 |
| Phase 4 — Décommission (4) | 1/4 | **4/4** ✅ |

**EPIC-001 = 100 % livré.** 13 sprints (007 → 013), ~165 pts engagés cumulés.

### Tests & Qualité

| Métrique | Avant sprint-013 | Après sprint-013 |
|---|---:|---:|
| Tests Unit total | 661 | **733** (+72) |
| Tests Application Use Case | 9 | **37** (+28) |
| Tests E2E DDD controllers | 4 (Client) | **16** (+ Project + Order + Invoice) |
| ADR cumulés | 11 | 11 |
| PHPUnit Notices (post #162) | 0 | 0 |

### Vélocité (6 derniers sprints)

| Sprint | Engagé | Livré |
|---|---:|---:|
| 008 | 26 | 26 |
| 009 | 22 | 22 |
| 010 | 18 | 18 |
| 011 | 14 | 14 |
| 012 | 15 | 15 |
| **013** | **11** | **11** |

Vélocité moyenne 6 sprints : **17,7 pts**. Tendance baissière maîtrisée
(scope choisi conservateur sur Phase 4 — critères ADR-0009 prioritaires).

---

## 🎬 Démonstration

### Phase 4 — Décommission complète (PR #165)

3 routes legacy supprimées :
- `/projects/new` → backed by `CreateProjectUseCase` (default tasks préservés)
- `/orders/new` → backed by `CreateOrderQuoteUseCase` (orderNumber auto-généré)
- `/invoices/new` → hybrid UC + legacy completion (issuedAt/dueDate/amount)

Routes alternatives `/{resource}/new-via-ddd` SUPPRIMÉES (3 routes).

### Escalator coverage step 3 (PR #164)

28 nouveaux tests Application :
- `CreateClientUseCaseTest` — 8 tests (mapping serviceLevel, notes, NoHandler)
- `CreateProjectUseCaseTest` — 10 tests (projectType, clientId resolve)
- `CreateOrderQuoteUseCaseTest` — 7 tests (contractType, projectId attach)

### Découverte (hors scope)

`OrderFlatToDddTranslator` bugué : `$flat->createdAt` accès protected. Story
`ORDER-TRANSLATOR-FLAT-TO-DDD-FIX` créée pour sprint-014.

---

## 💬 Feedback PO / Stakeholders

### Positif

- **EPIC-001 fini en 13 sprints** sans régression utilisateur. Strangler
  fig validé end-to-end.
- **3 décommissions parallèles dans 1 PR** : pattern réplicable confirmé,
  économie review overhead vs 3 PRs séparées.
- **Escalator coverage on-track** : step 3/5 livré sans dette.
- **Bug CI infra fixé en moins de 30 min** (OPS-008 PR #166) — montre
  réactivité sur déblocage.

### À améliorer

- **Bug latent OrderFlatToDddTranslator** (protected createdAt) découvert
  par tests, mais pas corrigé sprint-013. Sprint-014 obligatoire si on veut
  utiliser ce translator (actuellement contourné par `reconstitute()` direct).
- **CI checks PR #163** ont fail à cause de stale base : signale que les
  PRs longues à merger doivent rebase sur main avant les checks finaux.

### Nouvelles demandes

Aucune ce sprint (focus interne).

---

## 📅 Prochaines étapes — Sprint 014

EPIC-001 fini → cap shifte vers buffer + nouvelles initiatives :

| Story candidate | Pts | Priorité |
|---|---:|---|
| DDD-PHASE2-CONTRIBUTOR-ACL | 4 | Should (buffer héritage sprint-012/013) |
| DDD-PHASE2-VACATION-ACL | 4 | Should (buffer héritage) |
| ORDER-TRANSLATOR-FLAT-TO-DDD-FIX | 1 | Could (bug latent) |
| TEST-COVERAGE-004 (escalator step 4 : 35 → 40 %) | 2 | Should |
| EPIC-002 kickoff (à définir avec PO) | TBD | TBD |

**Engagement cible : 11 pts** (Contributor ACL + Vacation ACL + bug fix
+ escalator) avec 8 pts capacité libre pour EPIC-002 ou nouvelles US PO.

Cf. `sprint-014-buffer-and-tech-debt/sprint-goal.md`.
