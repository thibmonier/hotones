# Phase 11bis.2 - Plan de Tests & Qualité

**Date de création** : 31 décembre 2025
**Objectif** : Augmenter la couverture de tests de 19% à 60% (focus entités critiques)
**Durée estimée** : 3-4 jours

## État Initial

### Couverture Actuelle (31 décembre 2025)

```
Code Coverage Report:
  Classes:  8.31% (28/337)
  Methods: 21.41% (651/3041)
  Lines:   19.06% (4783/25090)

Tests: 458, Assertions: 1410 ✅
```

### Calcul de l'Objectif

- **Couverture actuelle** : 19.06% (4783/25090 lignes)
- **Couverture cible** : 60% (15054/25090 lignes)
- **Lignes à tester** : ~10 271 lignes supplémentaires
- **Gain nécessaire** : +40.94 points de pourcentage

## Stratégie de Priorisation

### Critères de Priorisation

1. **Impact métier** : Services critiques pour la rentabilité, analytics, planning
2. **Complexité** : Code complexe = plus de bugs potentiels
3. **ROI** : Nombre de lignes testées vs. effort requis
4. **Sécurité** : Composants sensibles (upload, auth, payments)

### Composants Prioritaires

#### 🔴 Priorité P0 (CRITIQUE - Jour 1)

| Composant | Couverture | Lignes | Gain Potentiel | Type de Test |
|-----------|------------|--------|----------------|--------------|
| **HrMetricsService** | 0.66% | 1/151 | +150 lignes | Unit |
| **SecureFileUploadService** | 0.97% | 1/103 | +102 lignes | Unit |
| **ClientRepository** | 7.69% | 1/13 | +12 lignes | Integration |
| **UserRepository** | 16.67% | 1/6 | +5 lignes | Integration |
| **Total P0** | - | - | **~269 lignes** | - |

**Justification P0** :
- HrMetricsService : 0 tests, calculs RH critiques (TACE, disponibilité)
- SecureFileUploadService : sécurité uploads, risque XSS/malware
- Repositories : données utilisateurs, accès critique

#### 🟠 Priorité P1 (HAUTE - Jours 1-2)

| Composant | Couverture | Lignes | Gain Potentiel | Type de Test |
|-----------|------------|--------|----------------|--------------|
| **MetricsCalculationService** | 25.42% | 61/240 | +179 lignes | Unit |
| **WorkloadPredictionService** | 34.01% | 67/197 | +130 lignes | Unit |
| **ProjectRiskAnalyzer** | 43.60% | 150/344 | +194 lignes | Unit |
| **StaffingMetricsRepository** | 38.61% | 78/202 | +124 lignes | Integration |
| **TimesheetController** | 57.09% | 294/515 | +221 lignes | Functional |
| **Total P1** | - | - | **~848 lignes** | - |

**Justification P1** :
- MetricsCalculationService : fallback analytics, KPIs critiques
- WorkloadPredictionService : prédictions charge 6 mois
- TimesheetController : plus gros controller, saisie temps critique

#### 🟡 Priorité P2 (MOYENNE - Jours 2-3)

| Composant | Couverture | Lignes | Gain Potentiel | Type de Test |
|-----------|------------|--------|----------------|--------------|
| **Project (Entity)** | 27.95% | 71/254 | +183 lignes | Unit |
| **HomeController** | 30.99% | 53/171 | +118 lignes | Functional |
| **ForecastingService** | 51.13% | 113/221 | +108 lignes | Unit |
| **ProfitabilityService** | 54.63% | 183/335 | +152 lignes | Unit |
| **Vacation (Entity)** | 21.88% | 14/64 | +50 lignes | Unit |
| **Total P2** | - | - | **~611 lignes** | - |

**Justification P2** :
- Project : entité centrale, relations complexes
- HomeController : dashboard principal, KPIs agrégés
- ProfitabilityService : calculs marge, budget

#### 🟢 Priorité P3 (BASSE - Jour 3-4)

| Composant | Couverture | Lignes | Gain Potentiel | Type de Test |
|-----------|------------|--------|----------------|--------------|
| **PublicController** | 16.67% | 2/12 | +10 lignes | Functional |
| **ClientContact (Entity)** | 11.54% | 3/26 | +23 lignes | Unit |
| **PerformanceReviewController** | 43.64% | 48/110 | +62 lignes | Functional |
| **AI Tools** (4 tools) | ~2-10% | 4/128 | +124 lignes | Unit |
| **Total P3** | - | - | **~219 lignes** | - |

**Justification P3** :
- Moins critiques pour le métier
- AI Tools : nouveaux, peu utilisés en prod
- PublicController : pages statiques

## Roadmap d'Exécution

### Jour 1 : Services Critiques (P0 + début P1)

**Matin (4h)** :
1. ✅ Analyse couverture actuelle
2. ✅ Création plan de tests
3. ⏳ **HrMetricsService** : tests unitaires
   - `calculateAvailability()` : calcul jours disponibles
   - `calculateUtilization()` : taux d'occupation
   - `calculateTACE()` : Taux Activité Contributeurs Effectifs
   - Edge cases : périodes chevauchantes, congés

**Après-midi (4h)** :
4. ⏳ **SecureFileUploadService** : tests unitaires
   - Validation MIME types, extensions
   - Détection malware (mock ClamAV)
   - Sanitization noms fichiers
   - Upload S3 (mock)
5. ⏳ **ClientRepository** + **UserRepository** : tests d'intégration
   - CRUD operations
   - Custom query methods

**Gain estimé Jour 1** : ~400 lignes (P0 + début P1)

### Jour 2 : Services Métier (P1)

**Matin (4h)** :
1. ⏳ **MetricsCalculationService** : tests unitaires
   - `calculateRevenueMetrics()` : CA, marges
   - `calculateProjectMetrics()` : rentabilité projets
   - `calculateMonthlyKpis()` : KPIs mensuels
   - Fallback logic (quand star schema vide)

**Après-midi (4h)** :
2. ⏳ **WorkloadPredictionService** : tests unitaires
   - `predictWorkload()` : prédiction charge 6 mois
   - `analyzeCapacity()` : capacité vs. demande
   - Seasonal patterns, tendances
3. ⏳ **ProjectRiskAnalyzer** : tests unitaires
   - `analyzeProjectRisks()` : risques projet
   - Budget drift, dépassements
   - Prédictions rentabilité

**Gain estimé Jour 2** : ~500 lignes (fin P1)

### Jour 3 : Controllers & Entities (P1 fin + P2)

**Matin (4h)** :
1. ⏳ **TimesheetController** : tests fonctionnels
   - GET `/timesheet` : affichage semaine
   - POST `/timesheet/save` : sauvegarde
   - Timer start/stop
   - Validation règles métier (min 0.125j)
2. ⏳ **StaffingMetricsRepository** : tests d'intégration
   - Calculs métriques staffing
   - Aggregations par période

**Après-midi (4h)** :
3. ⏳ **Project (Entity)** : tests unitaires
   - Lifecycle methods (prePersist, preUpdate)
   - Relations (tasks, orders, timesheets)
   - Business methods (getProgress, getMargin)
4. ⏳ **HomeController** : tests fonctionnels
   - Dashboard KPIs
   - Graphs data
   - Permissions par rôle

**Gain estimé Jour 3** : ~600 lignes (P1 fin + début P2)

### Jour 4 : Finalisation & Mutation Testing (P2 fin + P3)

**Matin (4h)** :
1. ⏳ **ForecastingService** + **ProfitabilityService** : tests unitaires
   - Prévisions CA (realistic/optimistic/pessimistic)
   - Calculs rentabilité
2. ⏳ **Vacation (Entity)** : tests unitaires
   - Validation dates
   - Calcul jours ouvrés
   - Overlap detection

**Après-midi (4h)** :
3. ⏳ Tests P3 (si temps disponible)
4. ⏳ **Configuration Infection** (mutation testing)
   - Installation & configuration
   - Baseline run sur nouveaux tests
   - Documentation résultats
5. ⏳ **Documentation finale** Phase 11bis.2

**Gain estimé Jour 4** : ~300 lignes (P2 fin + P3 + Infection)

## Projection de Couverture

| Fin de Journée | Lignes Testées | Couverture % | Gain vs. J0 |
|----------------|----------------|--------------|-------------|
| **J0 (initial)** | 4 783 | 19.06% | - |
| **J1** | 5 183 | 20.65% | +1.59% |
| **J2** | 5 683 | 22.65% | +3.59% |
| **J3** | 6 283 | 25.04% | +5.98% |
| **J4** | 6 583 | 26.24% | +7.18% |

**Note** : Projection conservatrice basée uniquement sur les composants P0-P3 listés ci-dessus.

### Chemin vers 60%

Pour atteindre **60% de couverture**, il faudrait tester ~10 271 lignes supplémentaires.

**Stratégie réaliste** :
- Phase 11bis.2 (4 jours) : **Objectif intermédiaire 30-35%** (focus P0-P2)
- Post-11bis : Tests continus sur nouveaux développements
- Lot 12 : Continuer montée en couverture vers 60%

**Priorisation P0-P2** permet de :
1. Sécuriser les composants critiques (0% → 80%+)
2. Tester la logique métier complexe (analytics, planning)
3. Couvrir les workflows utilisateurs principaux (timesheet, dashboard)

## Stratégies de Tests

### Tests Unitaires (Services & Entities)

**Framework** : PHPUnit + Foundry factories

**Pattern** :
```php
class HrMetricsServiceTest extends TestCase
{
    private HrMetricsService $service;

    protected function setUp(): void
    {
        $this->service = new HrMetricsService(
            $this->createMock(EmploymentPeriodRepository::class),
            $this->createMock(VacationRepository::class)
        );
    }

    public function testCalculateAvailabilityWithNoVacations(): void
    {
        // Given: un contributeur sans congés
        // When: calcul disponibilité
        // Then: 100% disponible
    }

    public function testCalculateAvailabilityWithVacations(): void
    {
        // Given: un contributeur avec 5j de congés sur 20j
        // When: calcul disponibilité
        // Then: 75% disponible (15j/20j)
    }
}
```

### Tests d'Intégration (Repositories)

**Framework** : PHPUnit + DAMA doctrine-test-bundle (transaction rollback)

**Pattern** :
```php
class StaffingMetricsRepositoryTest extends KernelTestCase
{
    private StaffingMetricsRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = self::getContainer()->get(StaffingMetricsRepository::class);
    }

    public function testFindMetricsByPeriod(): void
    {
        // Given: 10 metrics in DB for Q1 2025
        FactStaffingMetricsFactory::createMany(10, [
            'dimTime' => DimTimeFactory::new()->forQuarter(1, 2025)
        ]);

        // When: query Q1 2025
        $metrics = $this->repository->findByPeriod('2025-01-01', '2025-03-31');

        // Then: 10 metrics returned
        $this->assertCount(10, $metrics);
    }
}
```

### Tests Fonctionnels (Controllers)

**Framework** : Symfony WebTestCase + Foundry

**Pattern** :
```php
class TimesheetControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testTimesheetIndexAsIntervenant(): void
    {
        // Given: logged as ROLE_INTERVENANT
        $user = UserFactory::createOne(['roles' => ['ROLE_INTERVENANT']]);
        $this->client->loginUser($user->_real());

        // When: GET /timesheet
        $this->client->request('GET', '/timesheet');

        // Then: 200 OK, form displayed
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="timesheet"]');
    }

    public function testTimesheetSaveWithValidData(): void
    {
        // Given: logged user + project + task
        $user = UserFactory::createOne();
        $project = ProjectFactory::createOne();
        $task = ProjectTaskFactory::createOne(['project' => $project]);

        $this->client->loginUser($user->_real());

        // When: POST /timesheet/save with valid data
        $this->client->request('POST', '/timesheet/save', [
            'project_id' => $project->getId(),
            'task_id' => $task->getId(),
            'date' => '2025-12-31',
            'hours' => 4.0,
            '_token' => $this->generateCsrfToken('timesheet_save')
        ]);

        // Then: redirect + timesheet saved
        $this->assertResponseRedirects('/timesheet');
        $this->assertCount(1, TimesheetFactory::findBy(['contributor' => $user->getContributor()]));
    }
}
```

## Outils & Configuration

### PHPUnit

**Configuration actuelle** : `phpunit.xml.dist`
```xml
<coverage>
    <include>
        <directory suffix=".php">src</directory>
    </include>
    <exclude>
        <directory>src/Kernel.php</directory>
        <directory>src/DataFixtures</directory>
    </exclude>
</coverage>
```

**Commandes** :
```bash
# Tests complets avec couverture
docker compose exec app ./vendor/bin/phpunit --coverage-text

# Tests unitaires uniquement
docker compose exec app composer test-unit

# Tests d'intégration uniquement
docker compose exec app composer test-integration

# Tests fonctionnels uniquement
docker compose exec app composer test-functional

# Couverture HTML (pour analyse détaillée)
docker compose exec app ./vendor/bin/phpunit --coverage-html coverage/
```

### Infection (Mutation Testing)

**Installation** :
```bash
composer require --dev infection/infection
```

**Configuration** : `infection.json.dist`
```json
{
    "source": {
        "directories": ["src"]
    },
    "logs": {
        "text": "infection.log",
        "badge": {
            "branch": "main"
        }
    },
    "mutators": {
        "@default": true
    },
    "phpUnit": {
        "configDir": "."
    }
}
```

**Commandes** :
```bash
# Run mutation testing
docker compose exec app composer infection

# CI mode (max threads)
docker compose exec app composer infection-ci
```

## Métriques de Succès

### Objectifs Chiffrés

| Métrique | Initial | Cible J4 | Cible Finale (post-11bis) |
|----------|---------|----------|---------------------------|
| **Couverture lignes** | 19.06% | 26-30% | 60% |
| **Couverture méthodes** | 21.41% | 35-40% | 65% |
| **Couverture classes** | 8.31% | 15-20% | 40% |
| **MSI (Mutation Score)** | N/A | 70%+ | 80%+ |
| **Nombre de tests** | 458 | 550-600 | 800-1000 |

### Critères de Validation

✅ **Phase 11bis.2 réussie si** :
1. Couverture ≥ 30% (gain +11 points minimum)
2. Tous les services P0 testés (≥ 80% coverage)
3. Services P1 testés (≥ 60% coverage)
4. Controllers critiques testés (≥ 70% coverage)
5. Infection configuré et baseline établi
6. 0 tests en échec
7. Documentation complète (ce fichier mis à jour)

## Risques & Contraintes

### Risques Identifiés

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| **Code legacy difficile à tester** | 🔴 Haute | 🟡 Moyenne | Refactoring ciblé, mocks |
| **Dépendances externes (S3, ClamAV)** | 🟠 Moyenne | 🔴 Haute | Mocks, test doubles |
| **Manque de temps (3-4j serrés)** | 🟠 Moyenne | 🟡 Moyenne | Priorisation stricte P0-P1 |
| **Tests flaky (aléatoires)** | 🟡 Faible | 🟡 Moyenne | Fondry factories, fixtures |

### Contraintes

- ⏱️ **Temps limité** : 3-4 jours pour +11 points de couverture
- 🎯 **Focus qualité > quantité** : Mieux vaut 30% bien testés que 60% mal testés
- 🔒 **0 régression** : Tous les tests actuels doivent continuer à passer
- 📚 **Documentation** : Chaque test doit être auto-documenté (Given/When/Then)

## Suivi de Progression

### Checklist Quotidienne

**Jour 1** :
- [ ] HrMetricsService (100% methods)
- [ ] SecureFileUploadService (80% methods)
- [ ] ClientRepository + UserRepository (100% methods)
- [ ] Couverture ≥ 21%

**Jour 2** :
- [ ] MetricsCalculationService (80% methods)
- [ ] WorkloadPredictionService (80% methods)
- [ ] ProjectRiskAnalyzer (70% methods)
- [ ] Couverture ≥ 23%

**Jour 3** :
- [ ] TimesheetController (80% methods)
- [ ] StaffingMetricsRepository (80% methods)
- [ ] Project entity (80% methods)
- [ ] HomeController (70% methods)
- [ ] Couverture ≥ 26%

**Jour 4** :
- [ ] ForecastingService + ProfitabilityService (70% methods)
- [ ] Vacation entity (80% methods)
- [ ] Infection configuré et baseline
- [ ] Documentation finale
- [ ] Couverture ≥ 30%

## Références

### Documentation

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Testing Best Practices](https://symfony.com/doc/current/testing.html)
- [Foundry Documentation](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html)
- [Infection Documentation](https://infection.github.io/guide/)

### Fichiers Clés

- `phpunit.xml.dist` : Configuration PHPUnit
- `tests/` : Répertoire des tests
- `src/Factory/` : Foundry factories (fixtures)
- `.env.test` : Variables d'environnement test (SQLite)

---

**Dernière mise à jour** : 31 décembre 2025
**Statut** : ⏳ En cours - Jour 1 démarré
**Prochaine étape** : Tests HrMetricsService
