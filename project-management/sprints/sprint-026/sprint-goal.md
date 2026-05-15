# Sprint 026 — EPIC-003 Phase 5 continuation + Dette résiduelle + Migration prod

| Champ | Valeur |
|---|---|
| Numéro | 026 |
| Début | 2026-06-24 |
| Fin | 2026-07-08 |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme** (6ᵉ confirmation recalibrage durable visée) |
| Engagement ferme | **12 pts** — Phase 5 (5 pts) + Dette résiduelle (6 pts) + Migration prod (1 pt) |
| Statut backlog | **kickoff_pending** — scope décidé, décomposition prête ; formalisation Planning P1 (2026-06-23) |
| Prédécesseur | sprint-025 (clôturé 2026-05-15, 12/12 pts, EPIC-003 Phase 5 partial) |

---

## 🎯 Sprint Goal (provisoire — à figer Planning P1)

> « EPIC-003 Phase 5 continuation : KPI Marge moyenne portefeuille (US-117
> reporté sp-025) + extension drill-down sur 2 widgets restants (Conversion,
> Margin adoption). Solde dette résiduelle Sub-epic D (Mago cleanup ciblé,
> Coverage push 72→74 %, audit `skip-pre-push` markers). Exécution dry-run
> prod migration WorkItem.cost legacy (héritage sp-024 A-5 HIGH). »

---

## 📦 Engagement ferme (12 pts)

### EPIC-003 Phase 5 continuation (5 pts)

| ID | Titre | Pts | MoSCoW | Tâches |
|----|-------|----:|--------|-------:|
| US-117 | KPI Marge moyenne portefeuille | 3 | Must | 6 |
| US-119 | Extension drill-down Conversion + Margin | 2 | Should | 4 |

### Sub-epic D — Dette résiduelle (6 pts)

| ID | Titre | Pts | Source |
|----|-------|----:|--------|
| MAGO-LINT-BATCH-002 | Cleanup Mago résiduel (200-300 erreurs ciblées) | 2 | héritage sp-025 |
| COVERAGE-014 | Push coverage 72 → 74 % (services legacy) | 2 | héritage sp-025 backlog |
| TEST-FUNCTIONAL-FIXES-003 | Audit 6 `skip-pre-push` markers restants | 2 | héritage sp-006 |

### Migration prod (1 pt)

| ID | Titre | Pts | Source |
|----|-------|----:|--------|
| T-113-07 | Dry-run prod migration WorkItem.cost legacy | 1 | sp-024 retro A-5 HIGH (2 sprints reportés) — **PRE-5 cap libre satisfait** |

**Total : 12 pts ferme · 19 tâches · ~35h**

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

---

## 🔁 Actions héritées rétro sprint-025 (A-1 → A-8)

| ID | Action | Owner | Priorité | Intégration sprint-026 |
|---|---|---|---|---|
| A-1 | **Cap libre PRE-5 — story concrète J0** | PO | **HIGH** | ✅ **T-113-07 explicit (1 pt ferme)** |
| A-2 | Décision US-117 — sprint-026 ou backlog ? | PO | High | ✅ scopée sprint-026 (3 pts) |
| A-3 | Helper `KpiTestSupport` trait | Tech Lead | Medium | groupé T-119-04 |
| A-4 | Hook pre-commit `make mago` step | Tech Lead | Medium | groupé T-MAGO2-03 |
| A-5 | ADR pattern timestamping listeners | Tech Lead | Low | groupé T-117-03 |
| A-6 | Procédure Mago segmentée par règle (doc) | Tech Lead | Medium | groupé T-MAGO2-03 |
| A-7 | Décision Slack channel `#kpi-alerts-prod` | PO + Tech Lead | Low | atelier OPS-PREP J-2 |
| A-8 | Pagination drill-down volume seuil | PO | Low | groupé T-119-02 si seuil décidé |

### Carry-over actions sp-024 retro

| ID | Action | Statut sp-025 → sp-026 |
|---|---|---|
| A-1 sp-024 | `enablePullRequestAutoMerge` settings | ❌ re-héritage 3ᵉ fois |
| A-5 sp-024 | T-113-07 dry-run prod | ✅ **scopé sprint-026 (1 pt)** |
| A-6 sp-024 | Doc cache.kpi pool ADR | 🟡 groupé T-117-03 |
| A-7 sp-024 | Slack channel `#kpi-alerts-prod` | ❌ re-héritage (cf A-7 sp-025) |

---

## ✅ Pré-requis kickoff (PRE-1 → PRE-5)

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 (minimal si pas de nouvelles stories OPS-tagged ; T-113-07 = OPS) | PO + Tech Lead | J-2 (2026-06-22) | pending |
| PRE-2 | Mesurer coverage post sp-025 (cible 72 % atteinte → push 74 %) | Tech Lead | J-1 | pending (→ T-COV14-01) |
| PRE-3 | Décision PO scope sprint-026 | PO | Planning P1 | ✅ **décidé** : Phase 5 continuation + Dette + T-113-07 |
| PRE-4 | Stories sprint-026 spécifiées 3C + Gherkin | PO | J0 fin | ✅ **fait** : US-117 (sp-025) + US-119 (sp-026) |
| PRE-5 | **Cap libre 1-2 pts pré-allocation explicite (A-1 HIGH)** | PO | Planning P1 | ✅ **fait** : T-113-07 (1 pt ferme), satisfait A-1 sp-025 |

---

## 📅 Cérémonies planifiées

| Cérémonie | Date | Durée |
|---|---|---|
| Atelier OPS-PREP-J0 (T-113-07 + #kpi-alerts-prod) | 2026-06-22 (J-2) | 1h |
| Sprint Planning P1 (QUOI) | 2026-06-23 | 2h |
| Sprint Planning P2 (COMMENT) | 2026-06-24 (J0) | 2h |
| Daily Scrum | quotidien | 15 min |
| Backlog Refinement | 2026-07-01 (J+5) | 1h |
| Sprint Review | 2026-07-08 | 2h |
| Rétrospective | 2026-07-08 | 1h30 |

---

## 📊 Vélocité & capacité

| Sprint | Engagement ferme | Livré | Ratio |
|---|---:|---:|---:|
| sprint-022 | 12 | 13 | 1.08 |
| sprint-023 | 12 | 12 | 1.00 |
| sprint-024 | 12 | 12 | 1.00 |
| sprint-025 | 12 | 12 | 1.00 |

- **Vélocité moyenne 16 sprints** : 11.13 pts
- **Baseline ferme** : 12 pts (5× confirmés ; sp-026 = 6ᵉ visée)
- **Capacité sprint-026** : 12 pts ferme (cap libre intégré via T-113-07)

---

## ⚠️ Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| T-113-07 dry-run drift > 5 % → trigger abandon ADR-0013 cas 3 | Faible | Élevé | Runbook §5 + décision PO obligatoire |
| Régression dashboard sur extension drill-down 4 KPIs | Moyenne | Moyen | T-119-04 tests no-régression dédiés |
| Coef proba forecast US-114 / seuils US-117 arbitraires | Moyenne | Faible | Configurable hiérarchique US-108 |
| Mago `--rule X` segmenté + tests intermédiaires plus lent | Faible | Faible | Procédure documentée sp-025 retro S-1 |

---

## 📝 Notes

- 6ᵉ confirmation baseline 12 pts visée (sp-021..025 = 5×).
- Pattern KpiCalculator 7ᵉ application via US-117 (réutilise `MarginAdoptionCalculator` US-112 partiel).
- A-1 HIGH cap libre TBD résolu : T-113-07 dans le ferme (pas de slot reserved vide).
- Renommer dossier `sprint-026` → `sprint-026-epic-003-phase-5-continuation` après Planning P1.
