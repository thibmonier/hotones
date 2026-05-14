# Sprint 025 — Kickoff (scope à définir Planning P1)

| Champ | Valeur |
|---|---|
| Numéro | 025 |
| Début | 2026-06-10 |
| Fin | 2026-06-24 |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme + 1-2 pts libre** (5ᵉ confirmation recalibrage durable) |
| Engagement ferme | **TBD** — décision PO Sprint Planning P1 (2026-06-09) |
| Statut backlog | **kickoff_pending** — atelier OPS-PREP-J0 à conduire J-2 (2026-06-08) |
| Prédécesseur | sprint-024 (clôturé 2026-05-13, 12/12 pts, EPIC-003 Phase 4 complete) |

---

## 🎯 Sprint Goal (provisoire — à figer Planning P1)

> « À définir Sprint Planning P1. EPIC-003 clôturé sprint-024 (100 %).
> Sprint-025 ouvre une nouvelle direction : Sub-epic D dette technique,
> EPIC-003 Phase 5 (extensions KPI), ou nouveau KPI revenue forecast
> (pattern KpiCalculator réutilisable). »

**Contexte** : EPIC-003 « WorkItem & Profitability » est clôturé à 100 %
(6 sprints, sprint-019 → sprint-024). Sprint-025 démarre sans EPIC porteur
défini — la décision de scope (PRE-3) est le premier livrable du Planning P1.

---

## 📦 Candidats scope (décision PO PRE-3)

### Option A — Sub-epic D dette technique (carry-over)

| ID | Titre | Points | Source |
|---|---|---:|---|
| MAGO-LINT-BATCH-001 | Mago lint cleanup batch initial (100-150 errors) | 2 | sp-022 retro #3, sp-023 retro A-6, hérité sp-024 |
| VACATION-REPO-AUDIT | Audit VacationRepository Deptrac violation | 1 | sp-023 retro L-4, hérité sp-024 |
| TEST-COVERAGE-013 | Coverage anticipation 70 → 72 % | 1 | PRE-2 héritage sp-024 |

**Sous-total carry-over : 4 pts** — insuffisant pour 12 pts ferme seul.

### Option B — EPIC-003 Phase 5 (extensions post-MVP)

Extensions DSO / lead time, nouveau KPI revenue forecast. Pattern
`KpiCalculator` établi 4× consécutifs sprint-024 → réplicable sans
réinvention archi (Domain Calculator + Repository port + Cache decorator
+ Widget + Slack + Integration E2E, ~11h pour 3 pts).

### Option C — Nouvel EPIC

Décision PO selon priorités produit post-EPIC-003.

> **Recommandation Tech Lead** : Option A (4 pts dette) + Option B ou C
> (8 pts feature) pour atteindre 12 pts ferme. Sub-epic D solde la dette
> accumulée sur 3 sprints avant d'ouvrir un nouvel EPIC.

---

## 📋 Definition of Done (rappel projet)

- [ ] PHPStan niveau max — 0 erreur
- [ ] PHP-CS-Fixer / Rector / Deptrac — 0 violation
- [ ] Tests : couverture ≥ 80 % (cible 72 % CI globale)
- [ ] Architecture Clean/DDD respectée (Deptrac vert)
- [ ] Code review approuvée
- [ ] 0 commit `--no-verify`
- [ ] Déployable (CI verte avant merge)

---

## 🔁 Actions héritées rétro sprint-024 (A-1 → A-7)

| ID | Action | Owner | Priorité | Deadline |
|---|---|---|---|---|
| A-1 | Activer `enablePullRequestAutoMerge` repo settings | Tech Lead | High | sprint-025 J-2 |
| A-2 | PRE-6 cap libre — assignement story concrète J0 | PO | High | Planning P1 |
| A-3 | Doc procédure rebase stack PR adjacent dans CONTRIBUTING.md | Tech Lead | Medium | doc-only |
| A-4 | Helper `DateTime::mutableFromImmutable()` dans Tests Support | Tech Lead | Medium | refactor 30 min |
| A-5 | T-113-07 dry-run prod user-tracked (post-merge sp-024) | user + Tech Lead | High | J0 maintenance window |
| A-6 | Doc cache.kpi pool partagé (ADR ou commentaire cache.yaml) | Tech Lead | Low | doc-only |
| A-7 | Décision Slack channel dédié `#kpi-alerts-prod` | PO + Tech Lead | Low | atelier OPS-PREP |

---

## ✅ Pré-requis kickoff (PRE-1 → PRE-5)

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 (runbook §2 + matrix §3) — minimal si pas de nouvelles stories OPS-tagged | PO + Tech Lead | J-2 (2026-06-08) | pending |
| PRE-2 | Mesurer coverage actuel post sprint-024 (cible 72 %) | Tech Lead | J-1 | pending |
| PRE-3 | Décision PO scope sprint-025 (Option A/B/C) | PO | Planning P1 (2026-06-09) | pending |
| PRE-4 | Stories sprint-025 spécifiées 3C + Gherkin | PO | J0 fin | pending (dépend PRE-3) |
| PRE-5 | Cap libre 1-2 pts pré-allocation explicite (A-2 héritage) | PO | Planning P1 | pending |

> ⚠️ **PRE-5 / A-2** : 3ᵉ sprint consécutif cap libre TBD (sp-023 ST-1 →
> sp-024 PRE-6 → sp-025). Priorité High — figer assignement story concrète
> AVANT kickoff, pas de slot reserved vide.

---

## 📅 Cérémonies planifiées

| Cérémonie | Date | Durée |
|---|---|---|
| Atelier OPS-PREP-J0 | 2026-06-08 (J-2) | 1h |
| Sprint Planning P1 (QUOI) | 2026-06-09 | 2h |
| Sprint Planning P2 (COMMENT) | 2026-06-10 (J0) | 2h |
| Daily Scrum | quotidien | 15 min |
| Backlog Refinement | 2026-06-17 (J+5) | 1h |
| Sprint Review | 2026-06-24 | 2h |
| Rétrospective | 2026-06-24 | 1h30 |

---

## 📊 Vélocité & capacité

| Sprint | Engagement ferme | Livré | Ratio |
|---|---:|---:|---:|
| sprint-021 | — | — | 1.17 |
| sprint-022 | 12 | 13 | 1.08 |
| sprint-023 | 12 | 12 | 1.00 |
| sprint-024 | 12 | 12 | 1.00 |

- **Vélocité moyenne 15 sprints** : 11.07 pts
- **Baseline ferme recalibré** : 12 pts (confirmé 4× consécutifs sp-021→024)
- **Capacité sprint-025** : 12 pts ferme + 1-2 pts libre

---

## ⚠️ Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Scope PRE-3 non décidé à temps | Moyenne | Élevé | Recommandation Tech Lead pré-câblée (Option A + B/C) |
| Carry-over dette (4 pts) insuffisant seul | Certaine | Moyen | Compléter avec feature 8 pts |
| Cap libre TBD 3ᵉ fois consécutive | Élevée | Faible | A-2 priorité High, figer J0 |
| T-113-07 dry-run prod en attente (A-5) | Moyenne | Moyen | Fenêtre maintenance J0 dédiée |

---

## 📝 Notes

- EPIC-003 clôturé 100 % sprint-024 — premier sprint post-EPIC depuis sprint-018.
- Pattern `KpiCalculator` (4× sp-024) documenté dans EPIC-003 roll-up — réutilisable.
- `sprint-status.yaml` stub sprint-025 : PR #268 mergée.
- Renommer le dossier sprint après décision scope PRE-3 (`sprint-025-<scope>`).
