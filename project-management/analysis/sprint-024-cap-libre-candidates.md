# Sprint-024 — Analyse Candidats Cap Libre

> **Source** : Sprint-023 retro S-2 + héritages A-6 + A-8
> **Date** : 2026-05-11
> **Auteur** : Tech Lead
> **Décision attendue** : Sprint-024 Planning P1 (2026-05-27)

---

## Contexte

Sprint-023 retro **ST-1** acte : *« Slot US-XXX reserved sans contenu → cap libre doit pointer story concrète pré-allouée »*. Sprint-024 cap libre 1-2 pts doit être pré-allouée explicite J-2.

Candidats identifiés retro S-2 :
1. **Mago lint cleanup batch initial** (A-6)
2. **Audit VacationRepository Deptrac violation** (A-8)
3. **Phase 4 anticipation US-110/111** — couverts par specs sprint-024 (PR #237)
4. **Redeploy Render PRE-5** — couvert par OPS-PRE5-DECISION

Cette analyse cible les 2 candidats restants.

---

## Candidat 1 — Mago lint cleanup batch initial

### Inventaire 626 errors (mago analyze)

```bash
docker compose exec app composer mago-lint
```

| Règle | Count | Effort fix | Auto-fixable |
|---|---:|---|---|
| `too-many-methods` | 130 | Élevé (architecture refactor) | ❌ |
| `no-empty` | 124 | Faible (suppression empty blocks) | ⚠️ partiellement |
| `cyclomatic-complexity` | 91 | Élevé (extract method) | ❌ |
| `final-controller` | 81 | **Trivial (1-line add `final`)** | ✅ |
| `kan-defect` | 58 | Élevé (cohesion métrique) | ❌ |
| `excessive-parameter-list` | 52 | Moyen (DTO refactor) | ❌ |
| `too-many-properties` | 44 | Élevé (split aggregate) | ❌ |
| `no-literal-password` | 33 | Moyen (env vars tests) | ❌ |
| `sensitive-parameter` | 12 | **Trivial (attribute `#[\SensitiveParameter]`)** | ✅ |
| `no-error-control-operator` | 1 | **Trivial (replace `@`)** | ✅ |
| **Total** | **626** | | |

### Stratification ROI

| Batch | Règles | Errors | Effort | Pts |
|---|---|---:|---|---:|
| **Quick wins** (sprint-024 cap libre) | final-controller + sensitive-parameter + no-error-control-operator | **94** | 1 sprint compactable | **1-2** |
| **Medium** (sprint-025+) | no-empty + no-literal-password | 157 | 1 sprint dédié | 3 |
| **Heavy refactor** (sprint-026+) | too-many-methods + cyclomatic-complexity + too-many-properties + excessive-parameter-list + kan-defect | 375 | 2-3 sprints dédiés | 8-13 |

### Recommandation Mago batch initial

**Sprint-024 cap libre 1-2 pts** :

```
Batch sprint-024 = 94 errors quick wins
- final-controller (81) : add `final` keyword sur controllers non hérités
- sensitive-parameter (12) : add #[\SensitiveParameter] sur passwords/tokens params
- no-error-control-operator (1) : remplacer `@function()` par try-catch
```

ROI :
- 94 / 626 errors removed = **15 % reduction sprint-024**
- Effort : 1.5-2 pts (≈ 3-4 h dev)
- Risque : Quasi-zero (modifications mécaniques sans logique)
- Pattern reproducible : batch sprint-025 (no-empty + no-literal-password) puis sprint-026+ refactor

### Risques

| Risque | Probabilité | Mitigation |
|---|---|---|
| `final` casse tests qui étendent controllers | Faible | Run pre-push test suite avant push |
| `#[\SensitiveParameter]` PHP 8.2+ pas supporté | Très faible | Stack déjà PHP 8.5 |
| Conflits avec dette legacy non vue | Faible | Batch isolé par règle, rollback granulaire possible |

---

## Candidat 2 — Audit VacationRepository Deptrac violation

### Investigation

Deptrac error message :

```
Skipped violation "App\Repository\VacationRepository" for "App\Entity\Vacation" was not matched.
```

Recherche source :

```bash
find src -name "VacationRepository.php"
# 0 résultats

ls src/Repository/Vacation*
# pas de fichiers
```

**Conclusion** : Le fichier `App\Repository\VacationRepository` **n'existe plus**. La violation skip dans `deptrac.yaml` référence un fichier supprimé (legacy pre-DDD migration EPIC-001 Phase 4).

### État actuel Vacation BC

Migration DDD **complète** :

```
src/Domain/Vacation/
  ├── Entity/
  ├── Event/
  ├── Exception/
  ├── Repository/VacationRepositoryInterface.php  ✅ interface domaine
  └── ValueObject/{VacationStatus, DailyHours, DateRange, VacationType}.php

src/Application/Vacation/
  ├── Command/  ✅
  ├── DTO/      ✅
  ├── Query/    ✅
  └── UseCase/  ✅

src/Infrastructure/Vacation/Persistence/Doctrine/
  └── DoctrineVacationRepository.php  ✅ implementation

src/Presentation/Vacation/
  ├── Controller/  ✅
  └── Form/        ✅
```

### Action réelle

**Pas un vrai problème architectural**. Juste cleanup config orphelin :

```yaml
# deptrac.yaml — retirer ligne skip orphan
# skip_violations:
#   App\Repository\VacationRepository:  <-- supprimer
#     - App\Entity\Vacation
```

Effort : **0.5 pt** (< 30 min) — cleanup config + run deptrac confirm 0 violation. Pas de migration code requise.

### Recommandation VacationRepository

**Pas candidat valable cap libre** (effort trop minimal). Plutôt :
- Inclure cleanup config dans **batch sprint-024** Mago quick wins (cohérent : nettoyage dette config)
- OU traiter en chore commit indépendant (5 min)

---

## Décision recommandée Planning P1

### Cap libre sprint-024 (1-2 pts)

**Option A — Mago batch quick wins** (RECOMMANDÉ) :
- 94 errors removed (15 % reduction)
- 1.5 pts effort
- ROI mesurable + pattern reproducible sprints futurs

**Option B — Mago batch + deptrac config cleanup** :
- 94 Mago errors + 1 violation deptrac
- 2 pts effort (batch + cleanup)
- Couvre A-6 + A-8 retro en 1 sprint

**Option C — Réserve cap libre pour absorption débordement Phase 4** :
- US-110/111/112/113 engagement 11 pts + OPS-PRE5-DECISION 2 pts = 13 pts
- Cap ferme 12 pts → OPS-PRE5-DECISION absorbe cap libre 1 pt
- Mago + VacationRepository reportés sprint-025

### Décision finale PO

**Recommandation Tech Lead** : **Option B** (couverture A-6 + A-8 ensemble, 2 pts cap libre).

Si engagement Phase 4 + OPS-PRE5-DECISION = 13 pts ne tient pas 12 ferme → Option C (cap libre absorbe OPS-PRE5, Mago report sprint-025).

---

## Annexe — Métriques baseline

### Mago errors évolution

| Sprint | Errors | Δ | Notes |
|---|---:|---:|---|
| sprint-021 | 627 | baseline (audit top-5 #3) | — |
| sprint-022 | 627 | 0 | Stable |
| sprint-023 | 626 | -1 | Drift mineur (US-106 refactor sans `final` ajouté ?) |
| **sprint-024 cible** | **532** | **-94** | Si Option B exécutée |

### Deptrac violations évolution

| Sprint | Violations | Skipped | Errors | Notes |
|---|---:|---:|---:|---|
| sprint-023 | 192 | 43 | 1 (VacationRepo orphan) | dette EPIC-001 résidu |
| **sprint-024 cible** | **192** | **42** | **0** | Cleanup orphan |

---

## Liens

- Sprint-023 retro S-2 : `../sprints/sprint-023-epic-003-phase-3-finition/sprint-retro.md`
- Sprint-022 retro top-5 #3 (audit Mago)
- Deptrac config : `../../deptrac.yaml`
- Mago config : `composer.json` scripts `mago-lint` / `mago-analyze`
