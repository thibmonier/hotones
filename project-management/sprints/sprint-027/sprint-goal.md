# Sprint 027 — OPS migration prod + dette résorption + features TBD Planning P1

| Champ | Valeur |
|---|---|
| Numéro | 027 |
| Début | 2026-07-08 (planning P1 réel) |
| Fin | 2026-07-22 |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme** (7ᵉ confirmation recalibrage durable visée) |
| Engagement ferme | **12 pts** prévu — détail Planning P1 |
| Statut backlog | **kickoff_pending** — scope partiellement décidé sp-026 retro, formalisation Planning P1 |
| Prédécesseur | sprint-026 (clôturé 2026-05-16, 11/12 pts, T-113-07 reporté) |

---

## 🎯 Sprint Goal (provisoire — à figer Planning P1)

> « Solde dette ops migration prod (T-113-07 dry-run + 2 stories OPS process
> capture) + cleanup Mago assertion-style 113 fixes via PR preview review
> + nouvelles features EPIC TBD (à scoper Planning P1). »

---

## 📦 Engagement provisoire (6 pts ops/dette confirmés + 6 pts features TBD)

### Ops migration prod (1 pt) — A-1 HIGH sp-026 retro

| ID | Titre | Pts | MoSCoW | Source |
|----|-------|----:|--------|--------|
| T-113-07 | Dry-run prod migration WorkItem.cost legacy | 1 | Must | sp-024 origine + sp-026 ferme reporté |

**Fenêtre maintenance prod planifiée Planning P1** (atelier OPS-PREP J-2).

### Sub-epic D dette résiduelle (5 pts)

| ID | Titre | Pts | MoSCoW | Source |
|----|-------|----:|--------|--------|
| MAGO-LINT-BATCH-003 | Cleanup assertion-style 113 fixes (`--unsafe` PR review) | 2 | Should | sp-026 défer |
| OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK | Script + Makefile check + hook retro + alerte Slack | 1 | Should | sp-026 captured |
| OPS-DEPENDENCY-FRESHNESS-CHECK | Script audit composer + yarn + cron Slack hebdo | 1 | Should | sp-026 captured |
| KPI-TEST-SUPPORT-TRAIT | Helper `KpiTestSupport` (Multi-tenant + cache + setUp) | 1 | Could | A-3 sp-025 héritée, A-7 sp-026 |

### Features EPIC-003 Phase 6 OU autre (6 pts) — **TBD Planning P1**

À scoper avec PO :
- Continuation EPIC-003 Phase 6 (post-Phase 5 complète) ?
- Nouveau EPIC scope produit ?
- Refonte dashboard 9 KPIs (UX/responsivité mobile — feedback stakeholders sp-026) ?
- Pagination drill-down 4 KPIs (A-8 sp-025 captured, feedback volume client) ?

**Total provisoire : 6 pts ferme + 6 pts TBD = 12 pts cible**

---

## 📋 Definition of Done (rappel projet)

- [ ] PHPStan niveau max — 0 erreur
- [ ] PHP-CS-Fixer / Rector / Deptrac — 0 violation
- [ ] Mago lint — 0 nouvelle issue (baseline activé)
- [ ] Tests : couverture ≥ 80 % (cible CI globale 74 %)
- [ ] Architecture Clean/DDD respectée
- [ ] Code review approuvée
- [ ] 0 commit `--no-verify`
- [ ] CI verte avant merge
- [ ] **Nouveau sp-027 : check migrations Doctrine appliquées prod avant clôture sprint** (DoD évolution OPS-SPRINT-CLOSURE-MIGRATIONS-CHECK livrée mid-sprint)

---

## 🔁 Actions héritées rétro sprint-026 (A-1 → A-7)

| ID | Action | Owner | Priorité | Intégration sp-027 |
|---|---|---|---|---|
| A-1 | **T-113-07 fenêtre maintenance prod planifiée** (3ᵉ tentative) | PO + Tech Lead | **HIGH** | ✅ ferme |
| A-2 | Tag `requires:ops-human` ou exclusion engagement-ratio | PO | High | ✅ Planning P1 |
| A-3 | PR preview `--unsafe` Mago assertion-style 113 fixes | Tech Lead | Medium | ✅ MAGO-LINT-BATCH-003 |
| A-4 | Scoper 2 OPS-* stories backlog (2 pts groupés) | PO | Medium | ✅ ferme |
| A-5 | Décision `batch-queue.yaml` (gitignore vs single PR) | Tech Lead | Medium | groupé OPS misc |
| A-6 | Slack channel `#kpi-alerts-prod` (4ᵉ tentative) | PO + Tech Lead | Low | atelier OPS-PREP |
| A-7 | Helper `KpiTestSupport` trait (héritée A-3 sp-025) | Tech Lead | Low | ✅ KPI-TEST-SUPPORT-TRAIT |

### Carry-over hérité sp-024/025

| ID origine | Action | Statut sp-027 |
|---|---|---|
| A-1 sp-024 | `enablePullRequestAutoMerge` settings | sp-027 OPS-PREP — 4ᵉ report |
| A-5 sp-024 + A-1 sp-026 | T-113-07 | ✅ ferme A-1 sp-027 |
| A-6 sp-024 | ADR cache.kpi pool partagé | sp-027 doc candidat |
| A-7 sp-024/025/026 | Slack `#kpi-alerts-prod` | ✅ A-6 sp-027 4ᵉ tentative |

---

## ⚠️ Risques

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| T-113-07 4ᵉ report si fenêtre maintenance non confirmée J-X | Moyenne | High | Atelier OPS-PREP J-2 ferme + créneau PO accepté |
| Mago `--unsafe` PR preview gros diff (113 fichiers tests) | Moyenne | Medium | Split par sous-dossier `tests/Unit/` puis `tests/Integration/` puis `tests/Functional/` |
| Features TBD Planning P1 non identifiées 6 pts | Faible | High | Atelier scope avec PO J-3 obligatoire |
| 2 OPS stories nécessitent accès prod (script check migrations) | Faible | Medium | SSH-tunnel ou read-only via `doctrine:migrations:status --env=prod` |

---

## 📅 Cérémonies (à confirmer)

| Cérémonie | Date proposée | Durée |
|---|---|---|
| OPS-PREP-J0 (atelier prep) | J-2 (2026-07-06) | 1h30 |
| Sprint Planning P1 (QUOI) | J0 (2026-07-08) | 2h |
| Sprint Planning P2 (COMMENT) | J0 (2026-07-08) | 2h |
| Daily Scrum | Quotidien | 15 min |
| Backlog Refinement | J+5 (2026-07-15) | 1h |
| Sprint Review | J+10 (2026-07-22) | 2h |
| Sprint Retro | J+10 (2026-07-22) | 1h30 |

---

## 📌 Notes

- Sprint-027 = première sprint **post-Phase 5 EPIC-003 complète** (9 KPIs livrés sp-024/025/026)
- Vélocité ancrée 6 sp ferme à 12 pts → baseline confirmée durable
- Focus sp-027 : solde dette ops + recapture process gaps + nouvelle phase à scoper
- Pattern autopilote `/project:run-queue` validé sp-026 → poursuite sp-027 avec exclusion stories `requires:ops-human`

---

**Auteur** : Tech Lead
**Date** : 2026-05-16 (kickoff stub)
**Version** : 0.1.0 (provisoire — figer Planning P1)
