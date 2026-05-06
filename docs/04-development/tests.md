# 🧪 Tests

Cette application dispose d'une batterie de tests couvrant plusieurs niveaux.

## Cible de couverture — escalier multi-sprint

> **Sprint-006** (TEST-COVERAGE-001) a révélé que la cible 45% initiale est **inatteignable en 3 pts story**. Reality check via `composer test-coverage-text` :
> Coverage actuel : **22.26% lignes** (7 123 / 31 992) — 379 classes sur 436 sans aucun test (87% du repo).

**Roadmap progressive validée** :

| Sprint | Cible coverage | Δ | Focus |
|--------|---------------:|--:|-------|
| sprint-006 (en cours) | **24.5-25%** | +2.3/+2.7 | Lot 1 services métier (Risk, Workload, Forecasting) + Lot 2 (TimesheetExport) |
| sprint-007 | 30% | +5 | TEST-MOCKS-003 : Notices `createMock` → `createStub` + Integration tests Workload/HubSpot |
| sprint-008 | 35% | +5 | Voters + Multi-tenant SQLFilter (gap-analysis #1-#3) — couvre nombreuses entités |
| sprint-009 | 40% | +5 | Repositories + Application Vacation (CQRS handlers complets) |
| sprint-010 | **45%** | +5 | Achievement de la cible originale — 4 sprints après l'engagement initial |

**Cible long-terme** : 60% sprint-013 (revue trimestrielle).

### Pourquoi un escalier et pas un saut

Reality check sprint-006 a montré :

1. **Controllers (80 fichiers)** se couvrent mieux via tests fonctionnels que unitaires — gain coverage indirect.
2. **Entités Doctrine** (63 fichiers) ne valent pas la peine d'être testées (getters/setters générés).
3. **Services métier riches** nécessitent souvent un graphe d'entités complet → Integration tests plus efficaces que Unit (cf `WorkloadPredictionService` : +1.2 pts en unit vs gain attendu en integration).
4. **Pure math helpers** (linearRegression, getMonthsDifference) → Unit test via reflection = ROI maximum (cf `ForecastingService` +8.2 pts).
5. **Services orchestration légère** (TimesheetExportService) → Unit test classique très efficace (0.88% → 100% en 5 tests).

### Mesure courante

```bash
# Génère le rapport texte avec PCOV
docker compose exec app composer test-coverage-text

# Rapport HTML détaillé navigable
docker compose exec app composer test-coverage-html
# Ouvre var/coverage/html/index.html
```

### Anti-cibles (à NE PAS prioriser)

| Catégorie | Raison |
|-----------|--------|
| `src/Controller/**` | Test fonctionnel > unit ; couverture indirecte |
| `src/Entity/**` | Getters/setters Doctrine, peu de logique |
| `src/Twig/**` | Coverage gain marginal, snapshot tests si nécessaire |
| `src/Domain/Vacation/**` | Déjà 100% via `tests/Application/Vacation/**` + `tests/Integration/**` |
| `src/Migrations/**` | Code transitoire one-shot |

## Types de tests
- Unitaires: Services et entités (calculs, formatage...)
- Intégration: Repositories Doctrine (requêtes, agrégats)
- Fonctionnels: Contrôleurs HTTP (WebTestCase) avec Foundry
- E2E (navigateur): Panther (Chrome headless)

## Exécution
```bash
# Tous les tests
./vendor/bin/phpunit

# Depuis Docker
docker compose exec app ./vendor/bin/phpunit
```

## Environnement de test
- `.env.test` configure une base SQLite locale pour des tests isolés.
- Les schémas sont (ré)initialisés automatiquement via ResetDatabase (Foundry).

## E2E avec Panther
Prérequis: Google Chrome/Chromium installé.

Variables utiles:
```bash
# Si nécessaire, pointer vers Chrome
export PANTHER_CHROME_BINARY="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
# Désactiver le sandbox si requis
export PANTHER_NO_SANDBOX=1
```

Lancement:
```bash
./vendor/bin/phpunit --testsuite default
```

## Intégration continue
Un workflow GitHub Actions exécute:
- PHPUnit (incluant E2E avec Chrome headless)
- Qualité: php-cs-fixer (dry-run), phpstan, phpmd

Voir: `.github/workflows/ci.yml`.
