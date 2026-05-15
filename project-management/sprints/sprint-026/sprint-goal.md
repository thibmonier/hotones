# Sprint 026 — Kickoff (scope à définir Planning P1)

| Champ | Valeur |
|---|---|
| Numéro | 026 |
| Début | 2026-06-24 |
| Fin | 2026-07-08 |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme + 1-2 pts libre** (6ᵉ confirmation recalibrage durable) |
| Engagement ferme | **TBD** — décision PO Sprint Planning P1 (2026-06-23) |
| Statut backlog | **kickoff_pending** — atelier OPS-PREP-J0 à conduire J-2 (2026-06-22) |
| Prédécesseur | sprint-025 (clôturé 2026-05-15, 12/12 pts, EPIC-003 Phase 5 partial) |

---

## 🎯 Sprint Goal (provisoire — à figer Planning P1)

> « À définir Sprint Planning P1. Candidats : continuation EPIC-003 Phase 5
> (US-117 KPI Marge moyenne portefeuille reporté sp-025), dette résiduelle
> (Mago 1307 issues progressif + Coverage push 5 services legacy), T-113-07
> dry-run prod migration WorkItem.cost (héritage 2 sprints). »

**Contexte** : EPIC-003 Phase 5 démarrée sp-025 avec 2 KPIs livrés (Revenue
forecast + Conversion rate) + drill-down/CSV widgets. US-117 (Marge moyenne
portefeuille, 3 pts) reporté backlog Phase 5. Pattern KpiCalculator établi
6× consécutifs — réplicable sans réinvention.

---

## 📦 Candidats scope (décision PRE-3)

### Option A — Continuation EPIC-003 Phase 5

| ID | Titre | Pts | Source |
|---|---|---:|---|
| US-117 | KPI Marge moyenne portefeuille | 3 | reporté sp-025 (spec créée) |
| US-118 | KPI nouveau (à définir PO) | 3 | extension Phase 5 |
| US-119 | Extension drill-down conversion / margin | 2 | post-feedback sp-025 |

### Option B — Dette résiduelle Sub-epic D

| ID | Titre | Pts | Source |
|---|---|---:|---|
| MAGO-LINT-BATCH-002 | Cleanup résiduel Mago (200-300 erreurs ciblées) | 2 | héritage sp-025 |
| COVERAGE-014 | Push 72 → 74 % (services legacy) | 2 | héritage sp-025 backlog |
| TEST-FUNCTIONAL-FIXES-003 | Audit `skip-pre-push` markers restants | 2 | héritage sp-006 sub-epic D |

### Option C — Migration prod (T-113-07)

| T-113-07 | Dry-run prod migration WorkItem.cost legacy | 1-2 | héritage sp-024 retro A-5 |

### Option D — Nouvel EPIC

Décision PO selon priorités produit post-Phase 5.

> **Reco Tech Lead** : Option A (3-8 pts continuation Phase 5) + Option B
> (2-4 pts dette résiduelle) = 12 pts ferme. Pattern viable sp-025.

---

## 📋 Definition of Done (rappel projet)

- [ ] PHPStan niveau max — 0 erreur
- [ ] PHP-CS-Fixer / Rector / Deptrac — 0 violation
- [ ] Mago lint — 0 nouvelle issue (baseline activé)
- [ ] Tests : couverture ≥ 80 % (cible CI globale 72 %, push 74 %)
- [ ] Architecture Clean/DDD respectée
- [ ] Code review approuvée
- [ ] 0 commit `--no-verify`
- [ ] CI verte avant merge

---

## 🔁 Actions héritées rétro sprint-025 (A-1 → A-8)

| ID | Action | Owner | Priorité | Deadline |
|---|---|---|---|---|
| **A-1** | **Cap libre PRE-5 — story concrète J0 (4ᵉ fois TBD inacceptable)** | PO | **HIGH** | Planning P1 |
| A-2 | Décision US-117 KPI Marge portefeuille — sp-026 ou backlog ? | PO | High | Planning P1 |
| A-3 | Helper `KpiTestSupport` trait (Multi-tenant + cache.kpi + ProjectFactory setUp) | Tech Lead | Medium | refactor 1h |
| A-4 | Hook pre-commit : ajouter `make mago` step | Tech Lead | Medium | OPS |
| A-5 | ADR pattern timestamping testabilité listeners (event.occurredOn) | Tech Lead | Low | doc-only |
| A-6 | Procédure Mago segmentée par règle (CONTRIBUTING.md) | Tech Lead | Medium | doc |
| A-7 | Décision Slack channel `#kpi-alerts-prod` (héritage 2 sprints) | PO + Tech Lead | Low | OPS-PREP |
| A-8 | Pagination drill-down US-116 — décision volume seuil | PO | Low | Planning P1 |

### Carry-over actions sprint-024 retro

| ID | Action | Statut sp-025 |
|---|---|---|
| A-1 sp-024 | `enablePullRequestAutoMerge` repo settings | ❌ Non fait — re-héritage 2ᵉ fois |
| A-5 sp-024 | T-113-07 dry-run prod migration WorkItem.cost | 🟡 Fenêtre maintenance à planifier sp-026 |
| A-6 sp-024 | Doc cache.kpi pool partagé ADR | 🟡 Implicite via comments — formaliser sp-026 |
| A-7 sp-024 | Décision Slack channel `#kpi-alerts-prod` | ❌ Non fait — re-héritage (cf A-7 sp-025) |

---

## ✅ Pré-requis kickoff (PRE-1 → PRE-5)

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 (minimal si pas de nouvelles stories OPS-tagged) | PO + Tech Lead | J-2 (2026-06-22) | pending |
| PRE-2 | Mesurer coverage actuel post sp-025 (cible 72 %, +24 tests Domain sp-025) | Tech Lead | J-1 | pending |
| PRE-3 | Décision PO scope sprint-026 (Option A/B/C/D) | PO | Planning P1 (2026-06-23) | pending |
| PRE-4 | Stories sprint-026 spécifiées 3C + Gherkin | PO | J0 fin | pending (dépend PRE-3) |
| PRE-5 | **Cap libre 1-2 pts pré-allocation explicite (A-1 priorité HIGH)** | PO | Planning P1 | **pending — 4ᵉ sprint TBD si non fixé** |

> ⚠️ **PRE-5 / A-1** : 4ᵉ sprint consécutif cap libre TBD (sp-023 ST-1 →
> sp-024 PRE-6 → sp-025 PRE-5 → sp-026 PRE-5). Priorité **HIGH** — figer
> assignement story concrète AVANT kickoff. Pas de slot reserved vide
> acceptable.

---

## 📅 Cérémonies planifiées

| Cérémonie | Date | Durée |
|---|---|---|
| Atelier OPS-PREP-J0 | 2026-06-22 (J-2) | 1h |
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
- **Baseline ferme** : 12 pts (confirmé 5× consécutifs sp-021→025 ; sp-026 = 6ᵉ confirmation visée)
- **Capacité sprint-026** : 12 pts ferme + 1-2 pts libre
- **ROI sp-025** : −14 % vs estim (dette plus rapide que prévu)

---

## ⚠️ Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Cap libre TBD 4ᵉ fois consécutive | Élevée | Faible | A-1 priorité HIGH, figer Planning P1 |
| T-113-07 dry-run prod toujours en attente | Moyenne | Moyen | Fenêtre maintenance J0 dédiée |
| Scope PRE-3 non décidé à temps | Moyenne | Élevé | Reco Tech Lead pré-câblée (Option A + B) |
| Mago résiduel 1307 issues — nouvelles issues sneak in | Faible | Faible | A-4 hook pre-commit Mago bloquera |

---

## 📝 Notes

- EPIC-003 Phase 5 partial (US-114/115/116 sp-025 done, US-117 backlog).
- Pattern `KpiCalculator` 6× consécutifs — documenter ADR si extension Phase 5.
- `sprint-status.yaml` stub sprint-026 : PR #290 mergée.
- Renommer le dossier `sprint-026` → `sprint-026-<scope>` après décision PRE-3.
