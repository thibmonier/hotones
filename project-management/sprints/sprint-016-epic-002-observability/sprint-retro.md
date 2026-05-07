# Sprint Retrospective — Sprint 016

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 016 — EPIC-002 Kickoff Observabilité & Performance |
| Date | 2026-05-07 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Atelier PO 5 questions ciblées** : décisions tranchées en 1 session courte. Format réutilisable EPICs futurs. |
| K-2 | **ADR-0012 trigger upgrade objectif** : quota 80 %, errors > 5/jour, > 30 users actifs. Décide automatique vs feeling subjectif. |
| K-3 | **Smoke test régression assertion explicite** : `<?php` raw source check (US-090 post-mortem encodé). Pattern à généraliser pour autres bugs résiduels. |
| K-4 | **Sentry déjà installé sprint-002** : pas de coût d'install à porter sprint-016. Sampling 5 % = ajustement minimal config. |
| K-5 | **Integration test Contributor pragmatique** : choix scope adaptatif (pas de Phase 3 controller → Integration repo plutôt que E2E artificiel). |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **DSN Sentry config manuelle Render dashboard** (sync: false). Pas d'automation auto-deploy. | Acceptable au stade. Future : secret GitHub Actions push vers Render API si justifié. |
| L-2 | **Smoke test scope limité (homepage + /health)** : assertions business absentes (login + create project). | Sprint-017+ : étendre scope si traction grandit (story US-SMOKE-EXTENDED). |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Synthèse atelier dans ADR systématique** : pour chaque décision PO, ADR avec contexte + alternatives écartées + trigger réversibilité. | Onboarding nouveau dev + audit décisions historiques. |
| S-2 | **Smoke test post-deploy = filet de sécurité par défaut** : pour toute story OPS/déploiement, ajouter assertion régression dans `post-deploy-smoke.yml`. | Évite multiples bugs latents type US-090. |
| S-3 | **Sprint-017 KPIs dashboard prod** = focus principal. Maintenant que CI green + observabilité installée, mesurer business compte. | EPIC-002 raison d'être : pilotage business + détection prod, dans cet ordre. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Étendre scope sprint mid-sprint sans atelier PO**. | Sprint-014 reshuffle PO mid-sprint a fonctionné mais coûteux. Préférer atelier court avant kickoff. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Format atelier PO 5 questions arbitrées** : kickoff EPIC efficace. À répliquer sprint-018 pour EPIC-003 (à scoper). | Pas de stagnation entre EPICs. |
| M-2 | **ADR pour décisions process** (sprint-012 ADR-0011 foundation, sprint-016 ADR-0012 stack obs). | Traçabilité décisions stratégiques. |

---

## 🎯 Actions concrètes Sprint 017

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | DSN Sentry configuré dans Render dashboard prod + staging (T-091-04 héritée) | Tech Lead | Sprint-017 J1 |
| A-2 | Verification post-deploy : provoquer erreur volontaire + check dashboard Sentry (T-091-05) | Tech Lead | Sprint-017 J2 |
| A-3 | Sprint-017 commitment : US-093 (5) + US-094 (3) + TEST-COVERAGE-006 (2) = 10 pts | Tech Lead | Sprint-017 J1 |
| A-4 | Pattern smoke test = filet sécurité par défaut (S-2) — règle CONTRIBUTING.md | Dev | Sprint-017 J5 |

---

## 📈 Trends 9 sprints

| Sprint | Engagé | Livré | Focus |
|---|---:|---:|---|
| 008 | 26 | 26 | DDD Phase 1 Client+Project |
| 009 | 22 | 22 | DDD Phase 1 Order + Phase 2 Client |
| 010 | 18 | 18 | DDD Phase 2 Project |
| 011 | 14 | 14 | DDD Phase 2 Order + Phase 3 |
| 012 | 15 | 15 | DDD Phase 4 Client + Invoice |
| 013 | 11 | 11 | DDD Phase 4 complète |
| 014 | 16 | 16 | OPS Stabilisation |
| 015 | 11 | 11 | Buffer Contributor + EPIC-002 brief |
| **016** | **11** | **11** | **EPIC-002 Kickoff Observabilité** |

Cumul 9 sprints : **144 pts livrés**. Vélocité moyenne **16 pts/sprint**.

---

## Directive Fondamentale Norm Kerth

> « Quel que soit ce que nous avons découvert, nous comprenons et croyons
> sincèrement que chacun a fait du mieux qu'il pouvait, étant donné ce qu'il
> savait à ce moment-là, ses compétences et capacités, les ressources
> disponibles, et la situation. »

---

## Conclusion

Sprint-016 = **100 % livré**, EPIC-002 démarré sur de bonnes bases :
- Atelier PO arbitré (5 questions)
- Sentry sampling configuré
- Smoke test post-deploy automatique (catch régression style US-090 en < 1h)
- Contributor ACL Integration tests (rattrapage 5ème BC)

Sprint-017 vise la **valeur business pure** : dashboard 7 KPIs +
alerting Sentry/Slack. Maintenant que la fondation observabilité existe,
exploitation par le PO devient pertinente.
