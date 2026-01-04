<?php

namespace App\Tests\Integration\Repository;

use App\Entity\EmploymentPeriod;
use App\Factory\ContributorFactory;
use App\Factory\ProfileFactory;
use App\Repository\EmploymentPeriodRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EmploymentPeriodRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private EmploymentPeriodRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(EmploymentPeriodRepository::class);
        $this->setUpMultiTenant();
    }

    public function testFindWithOptionalContributorFilterWithNoFilter(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();

        $this->createEmploymentPeriod($contributor1, '2025-01-01');
        $this->createEmploymentPeriod($contributor2, '2025-02-01');

        $results = $this->repository->findWithOptionalContributorFilter();

        // ContributorFactory auto-creates 1 period per contributor + 2 manual = 4 total
        $this->assertCount(4, $results);
    }

    public function testFindWithOptionalContributorFilterWithFilter(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();

        $this->createEmploymentPeriod($contributor1, '2025-01-01');
        $this->createEmploymentPeriod($contributor2, '2025-02-01');

        $results = $this->repository->findWithOptionalContributorFilter($contributor1->getId());

        // ContributorFactory auto-creates 1 period + 1 manual = 2 total
        $this->assertCount(2, $results);
        $this->assertEquals($contributor1->getId(), $results[0]->getContributor()->getId());
    }

    public function testHasOverlappingPeriodsWithNoOverlap(): void
    {
        $contributor = ContributorFactory::createOne();

        // Period 1: Jan 1 - Jan 31
        $this->createEmploymentPeriod($contributor, '2025-01-01', '2025-01-31');

        // Period 2: Feb 1 - Feb 28 (no overlap)
        $period2 = new EmploymentPeriod();
        $period2->setContributor($contributor);
        $period2->setStartDate(new DateTime('2025-02-01'));
        $period2->setEndDate(new DateTime('2025-02-28'));

        $hasOverlap = $this->repository->hasOverlappingPeriods($period2);

        $this->assertFalse($hasOverlap);
    }

    public function testHasOverlappingPeriodsWithOverlap(): void
    {
        $contributor = ContributorFactory::createOne();

        // Period 1: Jan 1 - Jan 31
        $this->createEmploymentPeriod($contributor, '2025-01-01', '2025-01-31');

        // Period 2: Jan 15 - Feb 15 (overlaps with Period 1)
        $period2 = new EmploymentPeriod();
        $period2->setContributor($contributor);
        $period2->setStartDate(new DateTime('2025-01-15'));
        $period2->setEndDate(new DateTime('2025-02-15'));

        $hasOverlap = $this->repository->hasOverlappingPeriods($period2);

        $this->assertTrue($hasOverlap);
    }

    public function testHasOverlappingPeriodsWithOpenEndedPeriod(): void
    {
        $contributor = ContributorFactory::createOne();

        // Period 1: Jan 1 - (no end date)
        $this->createEmploymentPeriod($contributor, '2025-01-01', null);

        // Period 2: Feb 1 - Feb 28 (overlaps because Period 1 is open)
        $period2 = new EmploymentPeriod();
        $period2->setContributor($contributor);
        $period2->setStartDate(new DateTime('2025-02-01'));
        $period2->setEndDate(new DateTime('2025-02-28'));

        $hasOverlap = $this->repository->hasOverlappingPeriods($period2);

        $this->assertTrue($hasOverlap);
    }

    public function testHasOverlappingPeriodsExcludesSelf(): void
    {
        $contributor = ContributorFactory::createOne();

        $period1 = $this->createEmploymentPeriod($contributor, '2025-01-01', '2025-01-31');

        // Check same period against itself (should not overlap when excluded)
        $hasOverlap = $this->repository->hasOverlappingPeriods($period1, $period1->getId());

        $this->assertFalse($hasOverlap);
    }

    public function testFindActivePeriods(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();

        // Active: no end date
        $this->createEmploymentPeriod($contributor1, '2024-01-01', null);

        // Active: end date in future
        $this->createEmploymentPeriod($contributor2, '2024-06-01', '2030-12-31');

        // Inactive: ended yesterday
        $yesterday = (new DateTime())->modify('-1 day');
        $this->createEmploymentPeriod($contributor1, '2023-01-01', $yesterday->format('Y-m-d'));

        $results = $this->repository->findActivePeriods();

        // Factory creates 2 active periods + 2 manual active = 4 total (1 inactive excluded)
        $this->assertCount(4, $results);
    }

    public function testFindByContributor(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();

        $this->createEmploymentPeriod($contributor1, '2024-01-01');
        // Factory creates period with startDate '-6 months', so use future date for test
        $this->createEmploymentPeriod($contributor1, '2030-01-01');
        $this->createEmploymentPeriod($contributor2, '2030-01-01');

        $results = $this->repository->findByContributor($contributor1);

        // Factory creates 1 period + 2 manual = 3 total
        $this->assertCount(3, $results);
        // Should be ordered by startDate DESC
        $this->assertEquals('2030-01-01', $results[0]->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2024-01-01', $results[2]->getStartDate()->format('Y-m-d'));
    }

    public function testFindCurrentPeriodForContributor(): void
    {
        $contributor = ContributorFactory::createOne();

        // Past period (ended)
        $this->createEmploymentPeriod($contributor, '2023-01-01', '2023-12-31');

        // Current period (active now) - more recent than factory period but still started
        $current = $this->createEmploymentPeriod($contributor, '2025-12-01', null);

        $result = $this->repository->findCurrentPeriodForContributor($contributor);

        $this->assertNotNull($result);
        // Should return most recent active period that has started (2025-12-01)
        $this->assertEquals($current->getId(), $result->getId());
    }

    public function testFindCurrentPeriodForContributorWithOnlyPastPeriods(): void
    {
        // Create contributor WITHOUT using factory to avoid auto-created active period
        $em          = static::getContainer()->get('doctrine')->getManager();
        $contributor = new \App\Entity\Contributor();
        $contributor->setFirstName('Test');
        $contributor->setLastName('User');
        $em->persist($contributor);
        $em->flush();

        // Only past periods
        $this->createEmploymentPeriod($contributor, '2020-01-01', '2020-12-31');

        $result = $this->repository->findCurrentPeriodForContributor($contributor);

        $this->assertNull($result);
    }

    public function testFindWithProfiles(): void
    {
        $contributor = ContributorFactory::createOne();
        $profile1    = ProfileFactory::createOne(['name' => 'Developer']);
        $profile2    = ProfileFactory::createOne(['name' => 'Designer']);

        $period = $this->createEmploymentPeriod($contributor, '2025-01-01');
        $period->addProfile($profile1);
        $period->addProfile($profile2);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();
        $em->clear();

        $results = $this->repository->findWithProfiles();

        // Factory creates 1 period + 1 manual = 2 total
        $this->assertCount(2, $results);
        // Find our period with profiles
        $periodWithProfiles = array_filter($results, fn ($p) => $p->getProfiles()->count() === 2);
        $this->assertCount(1, $periodWithProfiles);
    }

    public function testCalculatePeriodCost(): void
    {
        $contributor = ContributorFactory::createOne();

        // Period: Jan 1-5, 2025 (Wed-Sun = 3 working days: Wed, Thu, Fri)
        // CJM: 400 EUR
        // Work time: 100%
        // Expected cost: 3 * 400 = 1200 EUR
        $period = $this->createEmploymentPeriod($contributor, '2025-01-01', '2025-01-05');
        $period->setCjm(400.0);
        $period->setWorkTimePercentage('100.00');

        $cost = $this->repository->calculatePeriodCost($period);

        $this->assertEquals(1200.0, $cost);
    }

    public function testCalculatePeriodCostWithPartTime(): void
    {
        $contributor = ContributorFactory::createOne();

        // Period: Jan 1-5, 2025 (3 working days)
        // CJM: 400 EUR
        // Work time: 80% (part-time)
        // Expected cost: 3 * 0.8 * 400 = 960 EUR
        $period = $this->createEmploymentPeriod($contributor, '2025-01-01', '2025-01-05');
        $period->setCjm(400.0);
        $period->setWorkTimePercentage('80.00');

        $cost = $this->repository->calculatePeriodCost($period);

        $this->assertEqualsWithDelta(960.0, $cost, 0.0001); // Allow small float precision delta
    }

    public function testCalculatePeriodCostReturnsNullWhenNoCjm(): void
    {
        $contributor = ContributorFactory::createOne();

        $period = $this->createEmploymentPeriod($contributor, '2025-01-01', '2025-01-31');
        $period->setCjm(null);

        $cost = $this->repository->calculatePeriodCost($period);

        $this->assertNull($cost);
    }

    public function testCalculateWorkingDays(): void
    {
        // Jan 1-5, 2025: Wed, Thu, Fri, Sat, Sun = 3 working days
        $start = new DateTime('2025-01-01');
        $end   = new DateTime('2025-01-05');

        $workingDays = $this->repository->calculateWorkingDays($start, $end);

        $this->assertEquals(3, $workingDays);
    }

    public function testCalculateWorkingDaysFullWeek(): void
    {
        // Jan 6-12, 2025: Mon-Sun = 5 working days
        $start = new DateTime('2025-01-06');
        $end   = new DateTime('2025-01-12');

        $workingDays = $this->repository->calculateWorkingDays($start, $end);

        $this->assertEquals(5, $workingDays);
    }

    public function testGetStatistics(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();

        // 2 active periods
        $this->createEmploymentPeriod($contributor1, '2024-01-01', null)->setCjm(400.0);
        $this->createEmploymentPeriod($contributor2, '2025-01-01', '2030-12-31')->setCjm(500.0);

        // 1 past period
        $this->createEmploymentPeriod($contributor1, '2020-01-01', '2020-12-31')->setCjm(300.0);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $stats = $this->repository->getStatistics();

        $this->assertArrayHasKey('total_periods', $stats);
        $this->assertArrayHasKey('active_periods', $stats);
        $this->assertArrayHasKey('average_cjm', $stats);

        // Factory creates 2 periods + 3 manual = 5 total
        $this->assertEquals(5, $stats['total_periods']);
        // Factory creates 2 active + 2 manual active = 4 total
        $this->assertEquals(4, $stats['active_periods']);
        // Average should account for all periods with CJM
        $this->assertNotNull($stats['average_cjm']);
    }

    public function testCountDepartures(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();

        // Departure in Q1 2025
        $this->createEmploymentPeriod($contributor1, '2024-01-01', '2025-01-31');

        // Departure in Q2 2025
        $this->createEmploymentPeriod($contributor2, '2024-01-01', '2025-05-31');

        // No departure (still active)
        $this->createEmploymentPeriod($contributor1, '2025-02-01', null);

        $count = $this->repository->countDepartures(
            new DateTime('2025-01-01'),
            new DateTime('2025-03-31'),
        );

        $this->assertEquals(1, $count);
    }

    public function testCountActiveAt(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();

        // Active on 2025-01-15
        $this->createEmploymentPeriod($contributor1, '2024-01-01', '2025-06-30');

        // Active on 2025-01-15
        $this->createEmploymentPeriod($contributor2, '2025-01-01', null);

        // Not yet started on 2025-01-15
        $this->createEmploymentPeriod($contributor1, '2025-02-01', null);

        // Already ended on 2025-01-15
        $this->createEmploymentPeriod($contributor2, '2024-01-01', '2024-12-31');

        $count = $this->repository->countActiveAt(new DateTime('2025-01-15'));

        $this->assertEquals(2, $count);
    }

    public function testFindFirstByContributor(): void
    {
        $contributor = ContributorFactory::createOne();

        $this->createEmploymentPeriod($contributor, '2024-01-01');
        $first = $this->createEmploymentPeriod($contributor, '2023-01-01');
        $this->createEmploymentPeriod($contributor, '2025-01-01');

        $result = $this->repository->findFirstByContributor($contributor);

        $this->assertNotNull($result);
        $this->assertEquals($first->getId(), $result->getId());
    }

    // Helper method
    private function createEmploymentPeriod($contributor, string $startDate, ?string $endDate = null): EmploymentPeriod
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $period = new EmploymentPeriod();
        $period->setContributor($contributor);
        $period->setStartDate(new DateTime($startDate));

        if ($endDate !== null) {
            $period->setEndDate(new DateTime($endDate));
        }

        $em->persist($period);
        $em->flush();

        return $period;
    }
}
