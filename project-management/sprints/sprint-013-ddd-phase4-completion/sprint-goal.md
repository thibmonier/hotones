# Sprint 013 — DDD Phase 4 Completion + Coverage Step 3

| Champ | Valeur |
|---|---|
| Numéro | 013 |
| Début | 2026-05-07 |
| Fin | 2026-05-21 |
| Durée | 10 jours ouvrés |
| Capacité | 19 pts (vélocité 5 derniers sprints) |
| Engagement | **11 pts** + 8 pts buffer |

---

## 🎯 Sprint Goal

> « Compléter Phase 4 du strangler fig (3 décommissions Project + Order +
> Invoice), pousser l'escalator coverage à 35 % (step 3/5), et activer
> buffer Vacation/Contributor ACL si capacité. »

---

## Backlog engagé (11 pts)

### Sub-epic A — Phase 4 Completion (9 pts)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE4-DECOMMISSION-PROJECT-NEW | Supprimer route legacy `/projects/new`, garder `/projects/new-via-ddd` uniquement | 3 |
| DDD-PHASE4-DECOMMISSION-ORDER-NEW | Supprimer route legacy `/orders/new`, garder `/orders/new-via-ddd` uniquement | 3 |
| DDD-PHASE4-DECOMMISSION-INVOICE-NEW | Supprimer route legacy `/invoices/new`, garder `/invoices/new-via-ddd` uniquement | 3 |

### Sub-epic B — Tech Debt + Coverage (2 pts)

| ID | Titre | Pts |
|---|---|---:|
| TEST-COVERAGE-003 | Push coverage 30 → 35 % (escalator step 3 sur 5) — Use Cases + Application layer | 2 |

---

## Buffer (8 pts non engagés)

| ID | Titre | Pts | Activation si |
|---|---|---:|---|
| DDD-PHASE2-CONTRIBUTOR-ACL | Bridge Contributor BC | 4 | Phase 4 livrée J5 |
| DDD-PHASE2-VACATION-ACL | Bridge Vacation BC déjà DDD partiel | 4 | Phase 4 livrée J5 + Contributor ACL livré |

---

## Critères Phase 4 (rappel ADR-0009)

Avant chaque décommission :
1. ✅ Route DDD `/{resource}/new-via-ddd` mergée
2. ✅ Tests E2E feature parity (équivalence formulaire legacy ↔ DDD)
3. ✅ Code review approuvée par 1 reviewer
4. ✅ Smoke production sur fixtures (création + listing post-action)
5. ✅ Aucun appel résiduel à la route legacy (grep templates + JS)

---

## Definition of Done

- ✅ Tests Unit + E2E passent
- ✅ PHPStan max niveau → 0 erreur réelle
- ✅ ADR si décision architecturale ou process
- ✅ Aucune régression suite Unit (236 tests baseline)
- ✅ Coverage ≥ step 3 (35 %) sur classes touchées

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| DDD-PHASE4-DECOMMISSION-PROJECT-NEW | Tests E2E ProjectControllerDddTest | 🟡 à créer J1 |
| DDD-PHASE4-DECOMMISSION-ORDER-NEW | Tests E2E OrderControllerDddTest | 🟡 à créer J1 |
| DDD-PHASE4-DECOMMISSION-INVOICE-NEW | PR #159 + #160 mergées + tests E2E | 🟡 PRs + tests à créer |
| TEST-COVERAGE-003 | PRs sprint-012 mergées (target Use Cases) | 🟡 |
| DDD-PHASE2-CONTRIBUTOR-ACL | Phase 1 Contributor BC mergé | 🔴 pas commencé — story buffer |
| DDD-PHASE2-VACATION-ACL | Phase 1 Vacation BC déjà partiel | 🟢 |

---

## Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| PRs sprint-012 (#159/#160/#161/#162) pas mergées J1 | Moyenne | Moyen | Sprint-013 démarre sur tests E2E + escalator (pas bloqués) |
| Tests E2E Project/Order absents | Haute | Moyen | T-DPP3-01 / T-DPO3-01 prévues sprint-013 J1 |
| Cherry-pick foundation persiste malgré ADR-0011 | Faible | Faible | Review tech lead bloque réintroduction abstractions Shared |

---

## Cérémonies

| Cérémonie | Date | Durée |
|---|---|---|
| Sprint Planning | 2026-05-07 | (en cours, auto mode) |
| Daily | quotidien (auto mode) | — |
| Affinage Sprint-014 | J7 | 1 h |
| Sprint Review | 2026-05-21 | 2 h |
| Rétrospective | 2026-05-21 | 1 h 30 |

---

## Engagement Vélocité

Capacité : 19 pts (moyenne sprints 008..012).
Engagement : 11 pts (58 % de capacité).
Marge : 8 pts pour buffer + imprévus.

**Rationale faible engagement** : Phase 4 est nouvelle, ne pas compromettre
les critères ADR-0009 (UAT + E2E feature parity) sous pression vélocité.
