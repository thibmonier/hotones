# Sprint 015 — Buffer ACL Promotion + EPIC-002 Kickoff

| Champ | Valeur |
|---|---|
| Numéro | 015 |
| Début | 2026-05-08 |
| Fin | 2026-05-22 |
| Durée | 10 jours ouvrés |
| Capacité | 17 pts (vélocité 7 sprints précédents) |
| Engagement ferme | **11 pts** + 6 pts capacité libre EPIC-002 |

---

## 🎯 Sprint Goal

> « Livrer enfin le buffer Contributor + Vacation ACL (héritage 4 sprints),
> compléter l'escalator coverage à step 5/5 (40 → 45 %), et démarrer EPIC-002
> via atelier PO + premières user stories. »

---

## Backlog engagé (11 pts)

### Sub-epic A — Buffer héritage promotion FERME (8 pts)

| ID | Titre | Pts |
|---|---|---:|
| DDD-PHASE2-CONTRIBUTOR-ACL | Bridge Contributor BC (héritage sprints 011-014) | 4 |
| DDD-PHASE2-VACATION-ACL | Bridge Vacation BC complet (héritage sprints 011-014) | 4 |

⚠️ **Engagement irrévocable** : 4 sprints consécutifs de report. Plus du
tout du buffer = backlog dette. Promotion ferme actée sprint-014 retro
(action A-1 + ST-1).

### Sub-epic B — Coverage Escalator final (2 pts)

| ID | Titre | Pts |
|---|---|---:|
| TEST-COVERAGE-005 | Push coverage 40 → 45 % (escalator step 5/5 final) | 2 |

### Sub-epic C — EPIC-002 Kickoff (1 pt process)

| ID | Titre | Pts |
|---|---|---:|
| EPIC-002-KICKOFF-WORKSHOP | Atelier scope avec PO (1h) — produire MMF + 5 US candidates | 1 |

---

## Capacité libre (6 pts) — EPIC-002 Foundation

| ID | Titre | Pts |
|---|---|---:|
| EPIC-002-FOUNDATION (à définir post-atelier J1-J2) | Foundation technique selon scope retenu | TBD (≤ 6) |

---

## Definition of Done

- ✅ Tests Unit + E2E passent
- ✅ PHPStan max niveau → 0 erreur réelle
- ✅ ADR si décision architecturale
- ✅ Aucune régression suite Unit (784 tests baseline)
- ✅ Coverage ≥ step 5 (45 %)
- ✅ EPIC-002 brief écrit avant fin sprint J5
- ✅ **Live verification (`curl prod`)** sur chaque story OPS/déploiement
  (sprint-014 retro M-1)

---

## Dépendances

| Item | Dépend de | Status |
|---|---|---|
| DDD-PHASE2-CONTRIBUTOR-ACL | Phase 1 Contributor BC à compléter (additif) | 🔴 Phase 1 à faire en pré-requis |
| DDD-PHASE2-VACATION-ACL | Phase 1 Vacation BC déjà partiel (DTO + Query handlers) | 🟡 Phase 1 à compléter |
| TEST-COVERAGE-005 | Coverage step 4 (PR #169) mergé | 🟢 |
| EPIC-002-KICKOFF-WORKSHOP | Disponibilité PO | 🟡 à confirmer J1 |

---

## Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Phase 1 Contributor/Vacation BC plus chargée que prévu | Moyenne | Moyen | Découper en Phase 1 (2 pts) + Phase 2 (2 pts) si dépassement J5 |
| EPIC-002 scope non finalisable en 1 atelier | Moyenne | Faible | Sprint-016 affinage fallback, capacité libre 6 pts protégée |
| Cherry-pick foundation rejailler (ADR-0011 violation) | Faible | Faible | Code review tech lead bloque réintroduction |

---

## Cérémonies

| Cérémonie | Date | Durée |
|---|---|---|
| Sprint Planning Part 1 (avec PO) | 2026-05-08 | 1 h |
| Sprint Planning Part 2 (équipe dev) | 2026-05-08 | 1 h |
| **Atelier EPIC-002 Kickoff** | 2026-05-08 J1 | 1 h |
| Affinage Sprint-016 | 2026-05-15 J5 | 1 h |
| Sprint Review | 2026-05-22 | 2 h |
| Rétrospective | 2026-05-22 | 1 h 30 |

---

## Engagement Vélocité

Capacité : 17 pts (moyenne sprints 008..014).
Engagement ferme : 11 pts (65 % de capacité).
Capacité libre : 6 pts pour EPIC-002 Foundation post-atelier.

**Rationale capacité libre** : EPIC-002 scope inconnu jusqu'à J1 atelier.
Réserver 6 pts pour absorber les premières stories sans rééquilibrage
chaotique mid-sprint.
