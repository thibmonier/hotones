# Sprint 016 — EPIC-002 Kickoff : Observabilité & Performance

| Champ | Valeur |
|---|---|
| Numéro | 016 |
| Début | 2026-05-08 |
| Fin | 2026-05-22 |
| Durée | 10 jours ouvrés |
| Capacité | 17 pts (vélocité 8 sprints précédents) |
| Engagement ferme | **11 pts** + 6 pts capacité libre |

---

## 🎯 Sprint Goal

> « Démarrer EPIC-002 (Observabilité & Performance) : atelier scope PO
> finalisé, OpenTelemetry tracing intégré, smoke test post-deploy automatique
> + rattrapage E2E Contributor BC. »

---

## Backlog engagé (11 pts)

### Sub-epic A — EPIC-002 Foundation (9 pts)

| ID | Titre | Pts |
|---|---|---:|
| EPIC-002-KICKOFF-WORKSHOP | Atelier PO J1 (5 questions) — finaliser scope budget + stack + KPIs | 1 (process) |
| US-091 | OpenTelemetry tracing intégré (post-décision atelier) | 5 |
| US-092 | Smoke test post-deploy GH Action (homepage + /health) | 3 |

### Sub-epic B — Tech Debt Rattrapage (2 pts)

| ID | Titre | Pts |
|---|---|---:|
| TEST-CONTRIBUTOR-E2E | Tests E2E ContributorControllerDddTest (rattrapage pattern Client/Project/Order/Invoice) | 2 |

---

## Capacité libre (6 pts)

À allouer post-atelier J2 selon décisions PO :
- Si stack Sentry retenue → US-094 alerting peut démarrer (3 pts)
- Si Datadog/OTel native → études + ADR ADR-0012 (2 pts)
- Sinon : story coverage Contributor Application Use Cases (2 pts)
- OU buffer EPIC-001 résiduel (story OBSERVABILITY-DOMAIN-EVENTS)

---

## Definition of Done

- ✅ Tests Unit + E2E passent
- ✅ PHPStan max niveau → 0 erreur réelle
- ✅ ADR-0012 si décision stack observabilité (Sentry vs Datadog vs OTel native)
- ✅ Aucune régression suite Unit (824 tests baseline)
- ✅ Live verification (`curl prod`) sur stories OPS/déploiement (sprint-014 retro M-1)
- ✅ Smoke test post-deploy doit déclencher au prochain merge main

---

## Atelier EPIC-002 — 5 questions PO (Action A-1 sprint-015 retro)

1. **Budget mensuel observabilité** : $0 / $25 / $50 / $100+ ?
2. **Stack** : Sentry (free tier) vs Datadog (premium) vs OTel native + Loki/Grafana ?
3. **5 KPIs business prioritaires** à exposer dashboard ?
4. **Cold start Render** : payer plan starter ($7/mois) OU keep-alive externe gratuit ?
5. **Smoke test post-deploy scope** : minimum (homepage + /health) OU étendu (login + create project) ?

**Format** : 1 h synchrone OU async écrit avec délai 48 h (action A-1).

**Output attendu** : 5 user stories US-091..US-095 finalisées + sprint-016 commitment net.

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| US-091 OpenTelemetry | Décision atelier (stack) | 🔴 atelier J1 |
| US-092 Smoke test | GH Actions secret (URL prod + token) | 🟢 |
| TEST-CONTRIBUTOR-E2E | Contributor BC mergé sprint-015 | 🟢 |

---

## Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Atelier PO repoussé | Moyenne | Élevé | Async écrit 48h fallback (action A-1) |
| OpenTelemetry overhead > 50ms p95 | Faible | Moyen | Sampling 10 % + benchmark avant rollout |
| Stack décidée requiert plan payant | Moyenne | Faible | Sentry free tier suffit < 10k events/mois (fallback) |

---

## Cérémonies

| Cérémonie | Date | Durée |
|---|---|---|
| Sprint Planning Part 1 (avec PO) | 2026-05-08 | 1 h |
| Sprint Planning Part 2 (équipe dev) | 2026-05-08 | 1 h |
| **Atelier EPIC-002 PO** | 2026-05-08 J1 | 1 h |
| Affinage Sprint-017 | 2026-05-15 J5 | 1 h |
| Sprint Review | 2026-05-22 | 2 h |
| Rétrospective | 2026-05-22 | 1 h 30 |

---

## Engagement Vélocité

Capacité : 17 pts (moyenne sprints 008..015).
Engagement ferme : 11 pts (65 % de capacité).
Capacité libre : 6 pts pour absorber décisions atelier sans rééquilibrage.
