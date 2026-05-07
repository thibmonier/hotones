# Sprint 017 — EPIC-002 Dashboard + Alerting

| Champ | Valeur |
|---|---|
| Numéro | 017 |
| Début | 2026-05-08 |
| Fin | 2026-05-22 |
| Durée | 10 jours ouvrés |
| Capacité | 16 pts (vélocité 9 sprints précédents) |
| Engagement ferme | **10 pts** + 6 pts capacité libre |

---

## 🎯 Sprint Goal

> « Exposer la valeur EPIC-002 au PO via dashboard 7 KPIs business + alerting
> Sentry/Slack pour erreurs critiques. Pousser escalator coverage post-EPIC-001
> à 50 % (step 6 hors plan initial). »

---

## Backlog engagé (10 pts)

### Sub-epic A — EPIC-002 Suite (8 pts)

| ID | Titre | Pts |
|---|---|---:|
| US-093 | Dashboard 7 KPIs business prod (DAU/MAU + projets + devis + factures + conversion + revenu + marge) | 5 |
| US-094 | Alerting Sentry → Slack `#alerts-prod` (errors + quota Sentry) | 3 |

### Sub-epic B — Coverage Bonus (2 pts)

| ID | Titre | Pts |
|---|---|---:|
| TEST-COVERAGE-006 | Push coverage 45 → 50 % (escalator step 6 — hors plan initial) | 2 |

---

## Capacité libre (6 pts)

À allouer J3-J5 selon avancement ferme :
- ContributorController DDD route Phase 3 (option A — 2 pts)
- US-095 Logging structuré JSON anticipé sprint-018 (3 pts)
- BUFFER : Story SMOKE-PROD-EXTENDED (login + create project) — 3 pts

---

## Definition of Done

- ✅ Tests Unit + Integration passent
- ✅ PHPStan max niveau → 0 erreur réelle
- ✅ ADR si décision architecturale
- ✅ Aucune régression suite Unit (824 tests baseline)
- ✅ Live verification (`curl prod`) sur stories OPS/déploiement
- ✅ Smoke test post-deploy assertion ajoutée par story OPS (sprint-016 retro S-2)

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| US-093 Dashboard | Domain Aggregates 6 BCs (Client/Project/Order/Invoice/Vacation/Contributor) | 🟢 EPIC-001 fini |
| US-094 Alerting | DSN Sentry configuré Render dashboard (action A-1 sprint-016 retro) | 🟡 J1 manuel |
| TEST-COVERAGE-006 | Coverage step 5 (PR #183) | 🟢 mergé |

---

## Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| KPI calculation slow > 500ms (queries lourdes Doctrine) | Moyenne | Moyen | Cache Redis 5 min + queries optimisées |
| Slack webhook rate-limited | Faible | Faible | Sentry digest natif (1 message / 15 min) |
| Dashboard nécessite seed données | Élevée | Moyen | Fixtures dev + smoke prod fixtures |

---

## Cérémonies

| Cérémonie | Date | Durée |
|---|---|---|
| Sprint Planning | 2026-05-08 | 1 h |
| Daily | quotidien | — |
| Affinage Sprint-018 | 2026-05-15 J5 | 1 h |
| Sprint Review | 2026-05-22 | 2 h |
| Rétrospective | 2026-05-22 | 1 h 30 |

---

## Engagement Vélocité

Capacité : 16 pts (moyenne sprints 008..016).
Engagement ferme : 10 pts (62 % de capacité).
Capacité libre : 6 pts.

Sprint-018 sera le dernier EPIC-002 (US-095 Logging + ajustements + EPIC-003
kickoff possible). Sprint-019+ scope ouvert PO selon traction commerciale.
