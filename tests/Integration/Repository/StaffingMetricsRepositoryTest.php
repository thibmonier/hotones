<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Factory\ContributorFactory;
use App\Factory\DimProfileFactory;
use App\Factory\DimTimeFactory;
use App\Factory\FactStaffingMetricsFactory;
use App\Factory\ProfileFactory;
use App\Repository\StaffingMetricsRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class StaffingMetricsRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private StaffingMetricsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(StaffingMetricsRepository::class);
        $this->setUpMultiTenant();
    }

    public function testGetWeeklyOccupancyByContributor(): void
    {
        // Arrange
        $contributor = ContributorFactory::createOne(['firstName' => 'John', 'lastName' => 'Doe']);

        // Create weekly metrics for week 1 and 2 of 2024
        $week1 = DimTimeFactory::createOne(['date' => new DateTime('2024-01-01')]);
        $week2 = DimTimeFactory::createOne(['date' => new DateTime('2024-01-08')]);

        FactStaffingMetricsFactory::createOne([
            'dimTime'       => $week1,
            'contributor'   => $contributor,
            'granularity'   => 'weekly',
            'availableDays' => '5.00',
            'staffedDays'   => '4.00',
            'plannedDays'   => '0.50',
            'vacationDays'  => '0.00',
        ]);
        FactStaffingMetricsFactory::createOne([
            'dimTime'       => $week2,
            'contributor'   => $contributor,
            'granularity'   => 'weekly',
            'availableDays' => '5.00',
            'staffedDays'   => '3.00',
            'plannedDays'   => '1.00',
            'vacationDays'  => '0.00',
        ]);

        // Act
        $results = $this->repository->getWeeklyOccupancyByContributor(2024);

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals($contributor->getId(), (int) $results[0]['contributorId']);
        $this->assertEquals('John Doe', $results[0]['contributorName']);
        $this->assertEquals('2024-S01', $results[0]['weekNumber']);
        $this->assertEquals(90.0, $results[0]['occupancyRate']); // (4 + 0.5) / 5 * 100
        $this->assertEquals(0.5, $results[0]['remainingCapacity']); // 5 - 4.5
    }

    public function testGetWeeklyOccupancyByContributorWithProfileFilter(): void
    {
        // Arrange
        $profile     = ProfileFactory::createOne(['name' => 'Développeur']);
        $contributor = ContributorFactory::createOne(['firstName' => 'John', 'lastName' => 'Doe']);
        $dimProfile  = DimProfileFactory::createOne(['profile' => $profile, 'name' => 'Développeur']);
        $week1       = DimTimeFactory::createOne(['date' => new DateTime('2024-01-01')]);

        FactStaffingMetricsFactory::createOne([
            'dimTime'       => $week1,
            'dimProfile'    => $dimProfile,
            'contributor'   => $contributor,
            'granularity'   => 'weekly',
            'availableDays' => '5.00',
            'staffedDays'   => '4.00',
        ]);

        // Act
        $results = $this->repository->getWeeklyOccupancyByContributor(2024, $profile);

        // Assert
        $this->assertCount(1, $results);
    }

    public function testGetWeeklyGlobalTACE(): void
    {
        // Arrange
        $week1      = DimTimeFactory::createOne(['date' => new DateTime('2024-01-01')]);
        $dimProfile = DimProfileFactory::createOne(['isProductive' => true]);

        FactStaffingMetricsFactory::createOne([
            'dimTime'          => $week1,
            'dimProfile'       => $dimProfile,
            'granularity'      => 'weekly',
            'staffedDays'      => '17.00',
            'workedDays'       => '20.00',
            'contributorCount' => 3,
        ]);
        FactStaffingMetricsFactory::createOne([
            'dimTime'          => $week1,
            'dimProfile'       => $dimProfile,
            'granularity'      => 'weekly',
            'staffedDays'      => '19.00',
            'workedDays'       => '20.00',
            'contributorCount' => 2,
        ]);

        // Act
        $results = $this->repository->getWeeklyGlobalTACE(2024);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('2024-S01', $results[0]['weekNumber']);
        $this->assertEquals(5, (int) $results[0]['contributorCount']); // 3 + 2
        $this->assertEquals('36.00', $results[0]['staffedDays']); // 17 + 19
        $this->assertEquals('40.00', $results[0]['workedDays']); // 20 + 20
    }

    public function testGetWeeklyGlobalTACEExcludesNonProductiveProfiles(): void
    {
        // Arrange
        $week1 = DimTimeFactory::createOne(['date' => new DateTime('2024-01-01')]);

        // Productive profile
        $dimProfileProductive = DimProfileFactory::createOne(['isProductive' => true]);
        FactStaffingMetricsFactory::createOne([
            'dimTime'          => $week1,
            'dimProfile'       => $dimProfileProductive,
            'granularity'      => 'weekly',
            'contributorCount' => 3,
        ]);

        // Non-productive profile (should be excluded)
        $dimProfileNonProductive = DimProfileFactory::createOne(['isProductive' => false]);
        FactStaffingMetricsFactory::createOne([
            'dimTime'          => $week1,
            'dimProfile'       => $dimProfileNonProductive,
            'granularity'      => 'weekly',
            'contributorCount' => 1,
        ]);

        // No profile (should be included)
        FactStaffingMetricsFactory::createOne([
            'dimTime'          => $week1,
            'dimProfile'       => null,
            'granularity'      => 'weekly',
            'contributorCount' => 2,
        ]);

        // Act
        $results = $this->repository->getWeeklyGlobalTACE(2024);

        // Assert
        $this->assertCount(1, $results);
        // Should only count productive (3) + null profile (2) = 5
        $this->assertEquals(5, (int) $results[0]['contributorCount']);
    }

    public function testDeleteForDateRangeReturnsZeroWhenNoMatches(): void
    {
        // Arrange - no metrics created

        // Act
        $deleted = $this->repository->deleteForDateRange(
            new DateTime('2024-01-01'),
            new DateTime('2024-02-28'),
            'monthly',
        );

        // Assert
        $this->assertEquals(0, $deleted);
    }
}
