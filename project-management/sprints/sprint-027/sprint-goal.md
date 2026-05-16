# Sprint 027 — UX refonte dashboard + ops migration prod + dette ciblée

| Champ | Valeur |
|---|---|
| Numéro | 027 |
| Début | 2026-07-08 (Planning P1) |
| Fin | 2026-07-22 |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme** (7ᵉ confirmation recalibrage durable) |
| Engagement ferme | **12 pts** — scope figé post-Planning P1 (2026-05-16) |
| Statut backlog | **scope_figed** — 4 stories sélectionnées, prêt développement |
| Prédécesseur | sprint-026 (clôturé 2026-05-16, 11/12 pts, T-113-07 reporté) |

---

## 🎯 Sprint Goal (figé Planning P1)

> « Livrer la refonte UX du dashboard 9 KPIs (responsivité mobile + ordre
> lisible) en réponse au feedback stakeholders sp-026 + solde T-113-07
> dry-run prod migration WorkItem.cost legacy (3ᵉ tentative) + cleanup
> Mago assertion-style 113 fixes en 3 PRs reviewables + capture process
> sprint closure migrations Doctrine. »

---

## 📦 Engagement ferme (12 pts)

### Features EPIC-003 Phase 6 (8 pts)

| ID | Titre | Pts | MoSCoW | Source |
|----|-------|----:|--------|--------|
| US-120 | UX refonte dashboard 9 KPIs (responsivité mobile + ordre) | 8 | Must | Feedback stakeholders sp-026 + EPIC-003 Phase 6 candidat B |

### Ops migration prod (1 pt) — A-1 HIGH sp-026 retro

| ID | Titre | Pts | MoSCoW | Source |
|----|-------|----:|--------|--------|
| T-113-07 | Dry-run prod migration WorkItem.cost legacy | 1 | Must | sp-024 origine + sp-026 reporté (3ᵉ tentative) |

**Fenêtre maintenance prod : semaine 2 sp-027 (J5-J10)** — atelier OPS-PREP-J0 J-2.

### Sub-epic D dette ciblée (3 pts)

| ID | Titre | Pts | MoSCoW | Source |
|----|-------|----:|--------|--------|
| MAGO-LINT-BATCH-003 | Cleanup assertion-style 113 fixes — **3 PRs split Unit / Integration / Functional** | 2 | Should | sp-026 défer A-3 |
| OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK | Script + Makefile + hook retro + alerte Slack 24h | 1 | Should | sp-026 captured |

---

## 📋 Stories reportées sp-028

| ID | Titre | Pts | Raison report |
|---|---|---:|---|
| KPI-TEST-SUPPORT-TRAIT | Helper trait Multi-tenant + cache + setUp | 1 | Priorité UX > refactor (3ᵉ report A-3 sp-025) |
| OPS-DEPENDENCY-FRESHNESS-CHECK | Script audit composer + yarn + cron Slack | 1 | Priorité UX > process complementary |
| Slack `#kpi-alerts-prod` (A-7 sp-024..026) | Channel création | — | **5ᵉ tentative sp-028 — ADR-0019 Out Backlog si nouvel échec** |

---

## 📋 Definition of Done (rappel projet + évolution sp-027)

- [ ] PHPStan niveau max — 0 erreur
- [ ] PHP-CS-Fixer / Rector / Deptrac — 0 violation
- [ ] Mago lint — 0 nouvelle issue (baseline activé)
- [ ] Tests : couverture ≥ 80 % (cible CI globale 74 %)
- [ ] Architecture Clean/DDD respectée
- [ ] Code review approuvée
- [ ] 0 commit `--no-verify`
- [ ] CI verte avant merge
- [ ] **Nouveau sp-027 : check migrations Doctrine appliquées prod avant clôture** (DoD évolution OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK livrée mid-sprint)

---

## 🔁 Décisions Planning P1 (2026-05-16)

| Décision | Réponse | Owner |
|---|---|---|
| T-113-07 fenêtre maintenance | Semaine 2 sp-027 (J5-J10) | PO + Tech Lead |
| Phase 6 EPIC-003 scope #1 | B. UX refonte dashboard 9 KPIs | PO |
| UX refonte pts cible | 8 pts (réduit dette KpiTestSupport + Dep-check) | PO + Tech Lead |
| Mago `--unsafe` approche | 3 PRs split (Unit / Integration / Functional) | Tech Lead |
| Slack `#kpi-alerts-prod` | Report sp-028 (5ᵉ tentative — ADR-0019 si échec) | PO |
| `enablePullRequestAutoMerge` | ✅ Activé maintenant (2026-05-16) | Tech Lead |
| Tag `requires:ops-human` | YAML métadata + exclu pts ferme | PO + Tech Lead |
| `batch-queue.yaml` | Auto-merge inclus dans queue file | Tech Lead |

---

## 🔁 Actions héritées rétro sprint-026 (A-1 → A-7) — statut

| ID | Action | Statut sp-027 |
|---|---|---|
| A-1 | T-113-07 fenêtre maintenance prod | ✅ ferme J5-J10 |
| A-2 | Tag `requires:ops-human` + exclu engagement-ratio | ✅ Planning P1 décidé YAML métadata |
| A-3 | Mago `--unsafe` PR preview | ✅ ferme 3 PRs split |
| A-4 | 2 OPS stories backlog scope | ✅ 1 ferme (Migrations-check), 1 reporté sp-028 (Deps-check) |
| A-5 | `batch-queue.yaml` décision | ✅ auto-merge inclus queue file |
| A-6 | Slack `#kpi-alerts-prod` | ❌ Reporté sp-028 — ADR-0019 Out Backlog si échec |
| A-7 | KpiTestSupport trait | ❌ Reporté sp-028 (priorité UX) |

### Carry-over hérité sp-024/025

| ID origine | Action | Statut sp-027 |
|---|---|---|
| A-1 sp-024 | `enablePullRequestAutoMerge` | ✅ **Activé 2026-05-16** |
| A-5 sp-024 + A-1 sp-026 | T-113-07 | ✅ ferme A-1 sp-027 (3ᵉ tentative) |
| A-6 sp-024 | ADR cache.kpi pool partagé | sp-028 doc candidat |
| A-7 sp-024/025/026 | Slack `#kpi-alerts-prod` | ❌ sp-028 5ᵉ tentative |

---

## ⚠️ Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| US-120 UX refonte sous-estimée 8 pts | Moyenne | Haut | Wireframes pré-Planning P1 + scoping atelier UX |
| T-113-07 fenêtre maintenance non confirmée J-X | Faible | Haut | Atelier OPS-PREP-J0 J-2 ferme + créneau PO accepté |
| Mago 3 PRs split conflit séquentiel (rebase chains) | Moyenne | Medium | Stack PRs Unit→Integration→Functional, auto-merge active désormais |
| Slack `#kpi-alerts-prod` 5ᵉ échec sp-028 → ADR Out Backlog | Faible | Low | Préparation ADR-0019 anticipée sp-027 doc |

---

## 📅 Cérémonies

| Cérémonie | Date | Durée |
|---|---|---|
| Planning P1 (QUOI) | 2026-05-16 ✅ figé | 1h |
| OPS-PREP-J0 (atelier prep T-113-07) | J-2 sp-027 | 1h30 |
| Planning P2 (COMMENT — décomposition tâches) | J0 sp-027 | 2h |
| Daily Scrum | Quotidien | 15 min |
| Backlog Refinement (mid-sprint) | J+5 | 1h |
| Sprint Review | J+10 (2026-07-22) | 2h |
| Sprint Retro | J+10 (2026-07-22) | 1h30 |

---

## 📌 Notes

- Sprint-027 = **première sprint EPIC-003 Phase 6** (post-Phase 5 complète 9 KPIs)
- Focus UX refonte 8 pts = **plus gros story EPIC-003** (vs typique 2-3 pts KPIs)
- T-113-07 3ᵉ tentative — 4ᵉ report inacceptable (escalation board)
- auto-merge GitHub activé 2026-05-16 → workflow PRs accéléré sp-027+
- Pattern autopilote `/project:run-queue` poursuit sp-027 avec exclusion `requires:ops-human` (T-113-07 manuel)

---

**Auteur** : Tech Lead
**Date** : 2026-05-16 (Planning P1 figé)
**Version** : 1.0.0
