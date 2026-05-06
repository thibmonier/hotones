# Sprint Retrospective — Sprint 009

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 009 — DDD Phase 2 Strangler Fig + Critical Fixes |
| Date | 2026-05-06 |
| Format | Starfish |
| Animateur | Claude Opus 4.7 (1M context) |

## Directive Fondamentale

> "Quel que soit ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait son meilleur travail possible, compte tenu de ce qu'il savait à ce moment-là."

---

## ⭐ Starfish

### KEEP (Continuer)

| # | Item | Justification |
|---|---|---|
| K-1 | **Engagement conservateur (15/22 capacité)** | 14/15 livrés (93%). Le 1 déféré n'est pas un échec — c'est une investigation honnête révélant complexité hors scope. |
| K-2 | **Cherry-pick foundation duplicate (continued)** | Stack PRs en parallèle sur main sans dépendance circulaire. PRs #131 → #140 → #141 chaînées via cherry-pick. |
| K-3 | **ADR-first pour pattern complexe** | ACL-ADR-001 a été livré AVANT DDD-PHASE2-CLIENT-ACL. Pattern documenté = implémentation guidée. Évite refactorings tardifs. |
| K-4 | **Stratégie attribute légitime > refactor invasif (TEST-MOCKS-005)** | -100% notices avec 0 régression. Solution simple > 3 sprints d'efforts complexes. |
| K-5 | **Investigation systématique avant fix (SEC-MULTITENANT-FIX-001)** | A identifié que la régression venait d'un squash merge incomplet, pas d'un bug code. Fix précis = restoration du commit original. |

### LESS OF (Moins de)

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Foundation duplicate dans chaque PR DDD** | Sprint-010+: créer une branche parent foundation puis stack toutes les BCs dessus. |
| L-2 | **Tests Use Case CreateClient déférés** | Tests avec mocks complex Doctrine (persist+flush flow) plus longs à écrire. À l'avenir: créer un fixture-friendly TestableEntityManager. |

### MORE OF (Plus de)

| # | Item | Justification |
|---|---|---|
| M-1 | **Verify squash merges sur fichiers critiques** | SEC-MULTITENANT-FIX-001 a révélé qu'un squash peut perdre des changes. Sprint-010+: post-merge diff check sur multi-tenant + sécurité. |
| M-2 | **Honnêteté DoD via story déférée** | INVESTIGATE-CAT-C documentée comme déférée plutôt que livrée bâclement. Plus utile pour planning sprint-010. |
| M-3 | **Bridge VOs avec legacy fonctions** | ClientId.fromLegacyInt() pattern transitoire propre. Réutilisable Project/Order ACLs. |

### STOP (Arrêter)

| # | Item | Action |
|---|---|---|
| S-1 | **Se contenter de "ça compile"** | Sprint-007 PR #105 a retiré AllowMockObjectsWithoutExpectations en pensant que ça allait. Sprint-008/009 ont prouvé que c'était une erreur. À l'avenir: tester les 251 notices APRÈS retrait, pas seulement constater "tests passent encore". |

### START (Commencer)

| # | Item | Justification |
|---|---|---|
| ST-1 | **Sprint-010: stack PRs sur foundation** | Créer `feat/ddd-foundation` branche commune ou attendre merge des PRs DDD pour stop la duplication. |
| ST-2 | **Test integration Functional pour les use cases ACL** | Pour valider end-to-end: controller appelle UC, UC appelle ACL repo, ACL persist via flat repo. Sprint-010 candidat. |
| ST-3 | **CronCheckCriticalSecurityRegressions** | Hook GitHub Actions ou cron qui vérifie périodiquement que les tests sécurité critiques (TenantFilterRegressionTest) passent en CI complète. Détecte les régressions silencieuses comme SEC-MULTITENANT-FIX-001. |

---

## Action items prioritaires

| # | Action | Sprint cible | Pts |
|---|---|---|---:|
| A-1 | Créer DDD-PHASE2-PROJECT-ACL | 010 | 4 |
| A-2 | Créer DDD-PHASE2-ORDER-ACL | 010-011 | 4 |
| A-3 | Créer DDD-PHASE3-CONTROLLER-MIGRATION (1er controller) | 010-011 | 3-5 |
| A-4 | Investigate Cat C (déférée) | 010 | 1 |
| A-5 | Stack DDD foundation branche commune | 010 | (process) |
| A-6 | Test integration ACL end-to-end | 010 | 2 |

---

## Métriques rétro

| Métrique | S-005 | S-006 | S-007 | S-008 | **S-009** | Tendance |
|---|---:|---:|---:|---:|---:|:-:|
| Vélocité | 22 | 19 | 32 | 17 | **14** | ↘️ |
| Taux complétion | 100% | 86% | 100% | 100% | **93%** | ↘️ |
| Régressions production | 0 | 0 | 0 | 0 | **0** | = |
| ADRs créées | 1 | 0 | 0 | 4 | **2** | — |
| Stories déférées | 0 | 1 | 1 | 2 | **1** | = |
| **PHPUnit Notices résiduels** | — | — | 251 | 251 | **0** ✨ | ↘️↘️ |

---

## Highlights sprint-009

- 🎉 **PHPUnit Notices: 251 → 0 (-100%)** — TEST-MOCKS-005
- 🚨 **Régression critique multi-tenant fixée** — SEC-MULTITENANT-FIX-001
- 🏗️ **Pattern ACL validé** — DDD-PHASE2-CLIENT-ACL + use cases
- 📝 **2 ADRs** — 0004 functional failures (sprint-008 in retrospect) + 0008 ACL pattern
- 0 régression production
- 6 PRs, 14/15 pts (93%)

## Honnêteté DoD

INVESTIGATE-CAT-C (1 pt) déférée sprint-010. Investigation sérieuse a révélé que c'était lié à un détail Doctrine (refresh inverse-side après fixture) hors budget 1 pt. Mieux vaut differer qu'patcher bâclement.

---

## Sentiment équipe

```
😊 Très satisfait    [██████████████████░░] 88%   → 14/15 livrés, 0 régression, 0 notice
😐 Mixte             [██░░░░░░░░░░░░░░░░░░] 10%   → Cat C déférée, foundation duplication
😞 Frustré           [░░░░░░░░░░░░░░░░░░░░]  2%   → squash merge silently broken (sprint-007)
```

---

## Prochaines étapes

1. ✅ Rétrospective documentée
2. → `/workflow:start 010`
3. → `/project:decompose-tasks 010`
