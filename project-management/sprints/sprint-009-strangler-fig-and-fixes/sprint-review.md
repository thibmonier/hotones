# Sprint Review — Sprint 009

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 009 — DDD Phase 2 Strangler Fig + Critical Fixes |
| Date Review | 2026-05-06 |
| Durée | 1 jour (mode agentic accéléré) |
| Animateur | Claude Opus 4.7 (1M context) |

## Sprint Goal

> "Démarrer Phase 2 EPIC-001 (Strangler Fig via ACL) sur BC Client, fixer régression critique TenantFilter, résorber dette test résiduelle."

**Atteint : ✅ OUI** (14/15 pts, 93%, 1 story déférée)

---

## User Stories livrées

| ID | Pts | PR | Notes |
|---|---:|---|---|
| SEC-MULTITENANT-FIX-001 | 2 | #136 | Régression critique multi-tenant fixée |
| ACL-ADR-001 | 1 | #137 | ADR-0008 pattern Anti-Corruption Layer |
| INVESTIGATE-CAT-B | 1 | #138 | OnboardingTemplate selectors fixés |
| TEST-MOCKS-005 | 3 | #139 | **PHPUnit Notices 251→0 (-100%)** |
| DDD-PHASE2-CLIENT-ACL | 4 | #140 | ACL adapter + 2 translators |
| DDD-PHASE2-USECASE-001 | 3 | #141 | CreateClient + UpdateClient use cases |
| **Total** | **14** | **6 PRs** | **93%** |

Déférée:
- **INVESTIGATE-CAT-C** (1 pt) — VacationApproval pending API. Cause inverse-side relation non-refresh, hors scope sprint-009 (testée même avec PR #136 cherry-pick).

---

## Métriques

| Métrique | S-007 | S-008 | **S-009** | Tendance |
|---|---:|---:|---:|:-:|
| Points planifiés | 32 | 17 | **15** | ↘️ |
| Points livrés | 32 | 17 | **14** | ↘️ |
| Vélocité | 32 | 17 | **14** | ↘️ |
| Taux complétion | 100% | 100% | **93%** | ↘️ |
| PRs livrées | 10 | 8 | **6** | ↘️ |
| **PHPUnit Notices** | 251 | 251 | **0** | ↘️↘️ |
| Régressions Unit | 0 | 0 | **0** | = |
| ADRs créées | 0 | 4 | **2** (0004, 0008) | — |
| EPIC-001 progress | Phase 0 | Phase 1 ✅ | **Phase 2 amorcée** | ↗️ |

---

## Highlights sprint-009

### 🔥 Insight #1: TEST-MOCKS-005 = 100% notices clean

Re-application du `#[AllowMockObjectsWithoutExpectations]` (que PR #105 avait retiré) = **solution légitime PHPUnit pour pattern shared setUp**.

Effort: 3 pts. Impact: 251 → 0 PHPUnit Notices (-100%). 

Leçon: PR #105 avait sur-réagi en supprimant l'attribute. Sprint-007/008 (TEST-MOCKS-002/003/004) ont prouvé qu'aucune alternative ne tenait. Sprint-009 a re-instauré ce qui était le pattern correct dès le départ.

### 🚨 Insight #2: SEC-MULTITENANT-FIX-001 squash mergé incomplet

Le squash merge de PR #117 (sprint-007) avait perdu le bridge `CompanyOwnedInterface` dans `TenantFilter`. Régression sécurité critique restée 2 sprints (007→009) avant détection par INVESTIGATE-FUNCTIONAL-FAILURES (sprint-008 ADR-0004).

Leçon: les squash merges peuvent perdre des changes silencieusement. Vérifier le diff post-merge sur fichiers critiques.

### 🏗️ Insight #3: Pattern ACL livré + validé

Sprint-009 a validé le pattern Anti-Corruption Layer en bout-en-bout sur 1 BC (Client):

1. ADR-0008 documente le pattern
2. ACL adapter implémente l'interface DDD via délégation legacy
3. Translators bidirectional gèrent les divergences (ServiceLevel mapping)
4. Use cases (Create + Update) démontrent read-modify-save complet
5. ClientId.fromLegacyInt() bridge int auto-increment ↔ VO type-safe

Pattern réplicable pour Project + Order (sprints 010-011).

---

## Stories deferred / queue sprint-010

| Item | Pts | Origine |
|---|---:|---|
| INVESTIGATE-CAT-C | 1 | Déférée sprint-009 (inverse-side relation refresh) |
| DDD-PHASE2-PROJECT-ACL | 4 | Réplication ACL sur Project BC |
| DDD-PHASE2-ORDER-ACL | 4 | Réplication ACL sur Order BC |
| DDD-PHASE2-USECASE-002 | 3 | Use cases Project/Order |
| DDD-PHASE3-CONTROLLER-MIGRATION | 3-5 | Phase 3 démarrage (1er controller migré vers UC DDD) |
| DDD-PHASE1-INVOICE | 3 | Buffer non activé sprint-008 |
| TEST-COVERAGE-002 | 2 | Buffer non activé sprint-008 |

Queue sprint-010 estimée: ~20-22 pts (capacité historique 17-22 → marge OK).

---

## Impact backlog

| Action | ID | Description |
|---|---|---|
| Créée | DDD-PHASE2-PROJECT-ACL | Réplication ACL sur Project |
| Créée | DDD-PHASE2-ORDER-ACL | Réplication ACL sur Order |
| Créée | DDD-PHASE3-CONTROLLER-MIGRATION | 1er controller migré vers DDD use cases |
| Reportée | INVESTIGATE-CAT-C | sprint-010 |
| Status | EPIC-001 Phase 2 | Amorcée (Client BC complete) |

---

## Prochaines étapes

1. ✅ Sprint Review documenté (cette doc)
2. → `/workflow:retro 009` (rétrospective Starfish)
3. → `/workflow:start 010` (kickoff sprint-010)
