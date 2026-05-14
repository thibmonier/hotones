# Sprint 025 — EPIC-003 Phase 5 KPIs + Sub-epic D dette technique

| Champ | Valeur |
|---|---|
| Numéro | 025 |
| Début | 2026-06-10 |
| Fin | 2026-06-24 |
| Durée | 10 jours ouvrés |
| Capacité | **12 pts ferme + 1-2 pts libre** (5ᵉ confirmation recalibrage durable) |
| Engagement ferme | **12 pts** — EPIC-003 Phase 5 (8 pts) + Sub-epic D dette (4 pts) |
| Statut backlog | **kickoff_pending** — scope décidé, décomposition prête ; formalisation Sprint Planning P1 (2026-06-09) |
| Prédécesseur | sprint-024 (clôturé 2026-05-13, 12/12 pts, EPIC-003 Phase 4 complete) |

---

## 🎯 Sprint Goal (provisoire — à figer Planning P1)

> « EPIC-003 Phase 5 : 2 nouveaux KPIs business (Revenue forecast +
> Taux de conversion devis→commande) + extension drill-down/CSV des
> widgets DSO/lead time. En parallèle : solde de la dette technique
> Sub-epic D accumulée sur 3 sprints (Mago lint, Deptrac VacationRepo,
> coverage 70→72 %). »

**Contexte** : EPIC-003 « WorkItem & Profitability » clôturé sprint-024 (100 %,
6 sprints). Sprint-025 ouvre **Phase 5** (extensions KPI post-MVP) tout en
soldant la dette technique reportée 3 sprints consécutifs.

---

## 📦 Engagement ferme (12 pts)

### EPIC-003 Phase 5 — KPIs business (8 pts)

| ID | Titre | Pts | MoSCoW | Tâches |
|----|-------|----:|--------|-------:|
| US-114 | KPI Revenue forecast (prévision CA glissante) | 3 | Must | 6 |
| US-115 | KPI Taux de conversion devis → commande | 3 | Must | 6 |
| US-116 | Extension widgets DSO/lead time (drill-down + CSV) | 2 | Should | 4 |

### Sub-epic D — Dette technique carry-over (4 pts)

| ID | Titre | Pts | Source |
|----|-------|----:|--------|
| MAGO-LINT-BATCH-001 | Mago lint cleanup batch initial | 2 | sp-022/023/024 hérité |
| VACATION-REPO-AUDIT | Audit Deptrac VacationRepository | 1 | sp-023 retro L-4 hérité |
| TEST-COVERAGE-013 | Coverage 70 → 72 % | 1 | PRE-2 héritage sp-024 |

**Total : 12 pts ferme · 23 tâches · ~43h**

### Reporté → backlog Phase 5 (sprint-026+)

| US-117 KPI Marge moyenne portefeuille | 3 pts | overflow capacité |

---

## 📋 Definition of Done (rappel projet)

- [ ] PHPStan niveau max — 0 erreur
- [ ] PHP-CS-Fixer / Rector / Deptrac — 0 violation
- [ ] Tests : couverture ≥ 80 % (cible CI globale 72 %)
- [ ] Architecture Clean/DDD respectée (Deptrac vert)
- [ ] Code review approuvée
- [ ] 0 commit `--no-verify`
- [ ] CI verte avant merge

---

## 🔁 Actions héritées rétro sprint-024 (A-1 → A-7)

| ID | Action | Owner | Priorité | Intégration sprint-025 |
|---|---|---|---|---|
| A-1 | Activer `enablePullRequestAutoMerge` repo settings | Tech Lead | High | J-2, `technical-tasks.md` |
| A-2 | PRE-6 cap libre — assignement story concrète J0 | PO | High | Planning P1 (voir PRE-5) |
| A-3 | Doc procédure rebase stack PR adjacent — CONTRIBUTING.md | Tech Lead | Medium | groupé T-MAGO-03 |
| A-4 | Helper `DateTime::mutableFromImmutable()` — Tests Support | Tech Lead | Medium | refactor 30 min |
| A-5 | T-113-07 dry-run prod migration WorkItem.cost | user + Tech Lead | High | J0 fenêtre maintenance |
| A-6 | Doc cache.kpi pool partagé | Tech Lead | Low | groupé US-114 T-114-03 |
| A-7 | Décision Slack channel `#kpi-alerts-prod` | PO + Tech Lead | Low | atelier OPS-PREP J-2 |

---

## ✅ Pré-requis kickoff (PRE-1 → PRE-5)

| ID | Action | Owner | Deadline | Statut |
|---|---|---|---|---|
| PRE-1 | Atelier OPS-PREP-J0 (US-114/115 = nouveaux KPI, pas de nouvelles credentials → atelier minimal) | PO + Tech Lead | J-2 (2026-06-08) | pending |
| PRE-2 | Mesurer coverage actuel post sp-024 (cible 72 %) | Tech Lead | J-1 | pending (→ T-COV-01) |
| PRE-3 | Décision PO scope sprint-025 | PO | Planning P1 | ✅ **décidé** : Phase 5 KPI + Sub-epic D dette |
| PRE-4 | Stories sprint-025 spécifiées 3C + Gherkin | PO | J0 fin | ✅ **fait** : US-114/115/116/117 créées |
| PRE-5 | Cap libre 1-2 pts pré-allocation explicite (A-2) | PO | Planning P1 | pending — **3ᵉ sprint TBD, priorité High** |

> ⚠️ **PRE-5 / A-2** : cap libre 1-2 pts toujours non assigné (sp-023 ST-1 →
> sp-024 PRE-6 → sp-025). Candidat naturel : US-117 (3 pts, trop gros pour
> cap libre) ou item dette résiduelle. À figer Planning P1 — pas de slot vide.

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
- **Baseline ferme** : 12 pts (confirmé 4× consécutifs sp-021→024 ; sp-025 = 5ᵉ)
- **Capacité sprint-025** : 12 pts ferme + 1-2 pts libre

---

## ⚠️ Risques identifiés

| Risque | Probabilité | Impact | Mitigation |
|---|---|---|---|
| Cap libre TBD 3ᵉ fois consécutive | Élevée | Faible | A-2 priorité High, figer Planning P1 |
| T-113-07 dry-run prod en attente (A-5) | Moyenne | Moyen | Fenêtre maintenance J0 dédiée |
| Coefficient probabilité forecast arbitraire (US-114) | Moyenne | Faible | Configurable hiérarchique US-108, ajustable post-PO |
| Mago 626 erreurs — batch initial ne solde pas tout | Certaine | Faible | Cleanup progressif, baseline résiduel documenté |
| Index Doctrine manquants (`order.valid_until`, `order.created_at`) | Faible | Moyen | Vérif + migration en sous-tâche T-114-02 / T-115-02 |

---

## 📝 Notes

- EPIC-003 Phase 5 ouverte — Phase 4 clôturée sprint-024.
- Pattern `KpiCalculator` (6 tâches/KPI, ~11-12h pour 3 pts) réutilisé US-114/115.
- US-116 = extension UI (drill-down + CSV), pas de nouveau calculateur (4 tâches).
- Stack = Symfony web only — pas de tâches Flutter/mobile.
- Renommer le dossier `sprint-025` → `sprint-025-epic-003-phase-5-kpi` après Planning P1.
- Décomposition : `tasks/README.md`, `tasks/US-114/115/116-tasks.md`, `tasks/technical-tasks.md`, `task-board.md`.
