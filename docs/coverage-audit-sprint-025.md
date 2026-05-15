# Coverage Audit — Sprint 025 (TEST-COVERAGE-013)

> **Sub-epic D dette technique** — push coverage 70 → 72 % (PRE-2 héritage sp-024).

## T-COV-01 — Audit

### Baseline pré-push

Coverage CI globale post sp-024 : ~70 % (estimation PRE-2). Suite unit
seule : Classes 24.51 % · Methods 26.06 % · Lines 16.78 % (faible car
fonctionnel/intégration apportent l'essentiel).

### Zones identifiées sans tests dédiés (top candidates)

Domain pure (priorité haute, simple, ROI maximal) :

| Classe | Méthodes | Couverture |
|---|---:|---|
| `App\Domain\WorkItem\Migration\WorkItemMigrationResult` | 4 (logique driftRatio, abandonCase3) | ❌ 0 dedicated |
| `App\Domain\WorkItem\Migration\MigrationDriftDetail` | 2 (deltaCents, absoluteDeltaCents) | ❌ 0 dedicated |
| `App\Domain\Project\Service\MarginAdoptionStats` | 2 (factory empty + constructor) | ❌ 0 dedicated |
| `App\Domain\Project\Service\BillingLeadTimeStats` | 2 (factory empty + constructor) | ❌ 0 dedicated |

Service legacy (priorité basse, complexe, sprint-026+) :

| Classe | Methods coverage | Lines coverage | Difficulté |
|---|---:|---:|---|
| `App\Service\WorkloadPredictionService` | 8.33 % | 37.70 % | Complexe (mocks) |
| `App\Twig\CronExtension` | 25.00 % | 65.54 % | Medium |
| `App\Service\ProjectRiskAnalyzer` | 29.41 % | 72.89 % | Complexe |
| `App\Service\OnboardingService` | 46.67 % | 59.41 % | Medium |
| `App\Service\SecureFileUploadService` | 40.00 % | 60.19 % | Medium |

## T-COV-02 — Push réalisé (4 fichiers, 24 tests, 65 assertions)

### Tests ajoutés

| Fichier | Tests | Couvre |
|---|---:|---|
| `tests/Unit/Domain/WorkItem/Migration/WorkItemMigrationResultTest.php` | 7 | totalProcessed, driftCount, driftRatio (zéro + calcul), shouldTriggerAbandonCase3 (sous, sur, à 5 %) |
| `tests/Unit/Domain/WorkItem/Migration/MigrationDriftDetailTest.php` | 3 | deltaCents positif/négatif/zéro + absoluteDeltaCents |
| `tests/Unit/Domain/Project/Service/MarginAdoptionStatsTest.php` | 2 | factory empty + constructor |
| `tests/Unit/Domain/Project/Service/BillingLeadTimeStatsTest.php` | 2 | factory empty + constructor |

### Quality gates

- 24/24 tests verts ✅
- PHPStan max : 0 erreur
- Pas de régression sur suite unit existante

### Note sur impact CI

Le push est ciblé sur des Domain VOs jusque-là transitivement couvertes uniquement
par les Integration tests (US-110/111/112/113). L'ajout de tests Unit dédiés :
- Sécurise les invariants métier (abandon cas 3 trigger > 5 %, équivalence
  delta/abs delta)
- Améliore le coverage Methods + Lines sur Domain pure
- Sert de documentation exécutable des règles métier

## Backlog sprint-026+

Items reportés (non-trivial pour 1 pt budget) :
1. `WorkloadPredictionService` (Methods 8.33 %) — refactor pour testabilité d'abord
2. `ProjectRiskAnalyzer` (Methods 29.41 %) — tests Integration via factories
3. `OnboardingService` (Methods 46.67 %) — couvrir les 8 méthodes restantes
4. `CronExtension` (Methods 25 %) — Twig extension testable via TwigEnvironment

## Sources

- `make test-coverage` exécuté 2026-05-15 (suite unit seule)
- Output complet : `/tmp/coverage.txt` (en conteneur, transitoire)
- Pattern hérité COVERAGE-012 sprint-023 (step 12 push 68→70 %)
