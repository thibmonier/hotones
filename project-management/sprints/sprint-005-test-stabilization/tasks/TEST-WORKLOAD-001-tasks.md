# Tâches — TEST-WORKLOAD-001

## Informations

- **Story Points** : 3
- **MoSCoW** : Should
- **Origine** : TEST-007 sprint-004 hors-scope (workload alerts laissés non couverts)
- **Total estimé** : 7h

## Résumé

`AlertDetectionService::checkWorkloadAlerts` (sprint-004 src/Service/AlertDetectionService.php:134) construit un `QueryBuilder` Doctrine contre `StaffingMetricsRepository`. La complexité du mocking a fait sortir ce branche du scope TEST-007. Cette story le traite via une refacto léger (extraction de la query) + un test d'intégration léger.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TW-01 | [BE] | Extraire `calculateContributorWorkload($contributorId, $month)` vers `WorkloadCalculator` injectable | 2h | - | 🔲 |
| T-TW-02 | [TEST] | Unit test `AlertDetectionService::checkWorkloadAlerts` avec mock du `WorkloadCalculator` | 3h | T-TW-01 | 🔲 |
| T-TW-03 | [TEST] | Integration test `WorkloadCalculator` contre BDD réelle (DAMA bundle) | 1h | T-TW-01 | 🔲 |
| T-TW-04 | [REV] | Code review | 1h | T-TW-03 | 🔲 |

## Détail des tâches

### T-TW-01 — Extraction WorkloadCalculator

Avant :

```php
private function calculateContributorWorkload(int $contributorId, DateTimeImmutable $month): array
{
    $qb = $this->staffingMetricsRepository->createQueryBuilder('sm');
    // ... 30 lignes de QueryBuilder
}
```

Après :

```php
// Domain
interface WorkloadCalculatorInterface
{
    public function forContributor(int $contributorId, DateTimeImmutable $month): array;
}

// Infrastructure
final class DoctrineWorkloadCalculator implements WorkloadCalculatorInterface
{
    public function __construct(private StaffingMetricsRepository $repository) {}
    public function forContributor(int $contributorId, DateTimeImmutable $month): array { /* QB */ }
}

// Service (injection)
public function __construct(
    // ...
    private readonly WorkloadCalculatorInterface $workloadCalculator,
) {}
```

Maintenant `AlertDetectionService::checkWorkloadAlerts` est unit-testable avec un `WorkloadCalculatorInterface` mocké.

### T-TW-02 — Unit test

Compléter `AlertDetectionServiceTest` (existant sprint-004) avec :

```php
public function testWorkloadAlertDispatchedAboveCapacityThreshold(): void
{
    $contributor = $this->createMock(Contributor::class);
    $this->contributorRepository->method('findActiveContributors')->willReturn([$contributor]);

    $this->workloadCalculator->method('forContributor')->willReturn([
        'totalDays' => 25.0,
        'capacityRate' => 110.0,
    ]);

    $this->eventDispatcher->expects(self::atLeastOnce())
        ->method('dispatch')
        ->with(self::isInstanceOf(ContributorOverloadAlertEvent::class));

    $stats = $this->service->checkAllAlerts();
    self::assertGreaterThanOrEqual(1, $stats['overload_alerts']);
}

public function testWorkloadAlertSkippedBelowThreshold(): void { /* capacityRate=80 -> no dispatch */ }
public function testWorkloadAlertSkippedWhenNoMetrics(): void { /* capacityRate=0 -> no dispatch */ }
```

### T-TW-03 — Integration test

```php
final class DoctrineWorkloadCalculatorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testForContributorReturnsZerosWhenNoMetrics(): void
    {
        $calculator = self::getContainer()->get(DoctrineWorkloadCalculator::class);
        $result = $calculator->forContributor(999, new DateTimeImmutable('2026-05-01'));

        self::assertSame(0.0, $result['totalDays']);
        self::assertSame(0.0, $result['capacityRate']);
    }
}
```

### T-TW-04 — Review

Critères : 0 régression sur `checkAllAlerts`, suite verte, pas de nouveau service inutile.

## DoD

- [ ] `WorkloadCalculatorInterface` extrait + impl Doctrine
- [ ] 3 tests unit pour `checkWorkloadAlerts` (above, below, no-metrics)
- [ ] 1 test intégration pour `DoctrineWorkloadCalculator`
- [ ] PHPStan level 5 : 0 erreur
