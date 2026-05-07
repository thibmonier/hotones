# Sprint 014 — Buffer Activation + Tech Debt + EPIC-002 Kickoff

| Champ | Valeur |
|---|---|
| Numéro | 014 |
| Début | 2026-05-08 |
| Fin | 2026-05-22 |
| Durée | 10 jours ouvrés |
| Capacité | 18 pts (vélocité 6 sprints précédents) |
| Engagement | **11 pts** + 7 pts capacité libre EPIC-002 |

---

## 🎯 Sprint Goal

> « Stabiliser la chaîne de production : CI green, sécurité Snyk à jour,
> deps fresh, déploiement Render fonctionnel. Reporter buffer Contributor +
> Vacation ACL au sprint-015 (capacité absorbée par stories OPS urgentes
> US-087..US-090). »

---

## ⚠️ Reshuffle 2026-05-07

Stories OPS critiques injectées en cours de sprint (PO request) — voir
backlog OPS.md US-087..US-090 (Must / Should). Les stories Contributor +
Vacation ACL (8 pts) reportées sprint-015 (capacité absorbée).

État actuel (post-merge PRs #167 + #168 + #169) :

| Story | Pts | PR | Statut |
|---|---:|---|---|
| ORDER-TRANSLATOR-FLAT-TO-DDD-FIX | 1 | #168 | ✅ mergée |
| TEST-COVERAGE-004 | 2 | #169 | ✅ mergée |
| Sub-total fait | **3** | | |

Reste à livrer sprint-014 :

| Story | Pts | Priorité |
|---|---:|---|
| US-087 CI green | 5 | Must |
| US-088 Snyk security upgrades | 3 | Must |
| US-090 Render deploy fix | 3 | Must |
| US-089 Composer + npm update routine | 2 | Should |
| **Sub-total reste** | **13** | |

**Engagement total révisé : 3 livrés + 13 à faire = 16 pts** (vs 11 initial).

Capacité = 18 pts. Marge = 2 pts (faible).

Reportés sprint-015 :
- DDD-PHASE2-CONTRIBUTOR-ACL (4 pts)
- DDD-PHASE2-VACATION-ACL (4 pts)
- EPIC-002-KICKOFF-WORKSHOP (déplacé sprint-015 ou sprint-014 J9 si possible)

---

## Definition of Done

- ✅ Tests Unit + E2E passent
- ✅ PHPStan max niveau → 0 erreur réelle
- ✅ ADR si décision architecturale
- ✅ Aucune régression suite Unit (733 tests baseline)
- ✅ Coverage ≥ step 4 (40 %) sur classes touchées
- ✅ EPIC-002 brief écrit avant fin sprint

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| DDD-PHASE2-CONTRIBUTOR-ACL | Phase 1 Contributor BC à créer (additif) | 🔴 Phase 1 à faire en pré-requis |
| DDD-PHASE2-VACATION-ACL | Phase 1 Vacation BC déjà partiel (DTO + Query handlers existent) | 🟡 Phase 1 à compléter |
| ORDER-TRANSLATOR-FLAT-TO-DDD-FIX | Aucune | 🟢 |
| TEST-COVERAGE-004 | PRs sprint-013 mergées | 🟢 mergées |
| EPIC-002-KICKOFF-WORKSHOP | Disponibilité PO | 🟡 à confirmer J1 |

---

## Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Phase 1 Contributor/Vacation BC plus chargée que prévu (4 + 4 pts trop optimistes) | Moyenne | Moyen | Découper en 2 stories Phase 1 (2 pts) + Phase 2 (2 pts) si dépassement à J5 |
| EPIC-002 scope non finalisable en 1 atelier | Moyenne | Faible | Sprint-015 affinage fallback |
| Bug fix OrderFlatToDddTranslator révèle problèmes plus profonds (autres translators?) | Faible | Moyen | Audit rapide T-OTF-01 = 1 h pour vérifier les 7 autres translators |

---

## Cérémonies

| Cérémonie | Date | Durée |
|---|---|---|
| Sprint Planning Part 1 (avec PO) | 2026-05-08 | 1 h |
| Sprint Planning Part 2 (équipe dev) | 2026-05-08 | 1 h |
| Atelier EPIC-002 Kickoff | 2026-05-12 | 1 h |
| Affinage Sprint-015 | 2026-05-15 | 1 h |
| Sprint Review | 2026-05-22 | 2 h |
| Rétrospective | 2026-05-22 | 1 h 30 |

---

## Engagement Vélocité

Capacité : 18 pts (moyenne sprints 008..013).
Engagement ferme : 11 pts (61 % de capacité).
Capacité libre : 7 pts pour EPIC-002 selon scope post-atelier.

**Rationale capacité libre** : EPIC-001 fini, EPIC-002 scope inconnu.
Mieux vaut ne pas sur-engager, voir ce qui sort de l'atelier kickoff,
et ajuster sprint-015 avec un commitment EPIC-002 net.
