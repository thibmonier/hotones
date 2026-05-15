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
            'dimTime' => $week1,
            'contributor' => $contributor,
            'granularity' => 'weekly',
            'availableDays' => '5.00',
            'staffedDays' => '4.00',
            'plannedDays' => '0.50',
            'vacationDays' => '0.00',
        ]);
        FactStaffingMetricsFactory::createOne([
            'dimTime' => $week2,
            'contributor' => $contributor,
            'granularity' => 'weekly',
            'availableDays' => '5.00',
            'staffedDays' => '3.00',
            'plannedDays' => '1.00',
            'vacationDays' => '0.00',
        ]);

        // Act
        $results = $this->repository->getWeeklyOccupancyByContributor(2024);

        // Assert
        static::assertCount(2, $results);
        static::assertEquals($contributor->getId(), (int) $results[0]['contributorId']);
        static::assertSame('John Doe', $results[0]['contributorName']);
        static::assertSame('2024-S01', $results[0]['weekNumber']);
        static::assertSame(90.0, $results[0]['occupancyRate']); // (4 + 0.5) / 5 * 100
        static::assertSame(0.5, $results[0]['remainingCapacity']); // 5 - 4.5
    }

    public function testGetWeeklyOccupancyByContributorWithProfileFilter(): void
    {
        // Arrange
        $profile = ProfileFactory::createOne(['name' => 'Développeur']);
        $contributor = ContributorFactory::createOne(['firstName' => 'John', 'lastName' => 'Doe']);
        $dimProfile = DimProfileFactory::createOne(['profile' => $profile, 'name' => 'Développeur']);
        $week1 = DimTimeFactory::createOne(['date' => new DateTime('2024-01-01')]);

        FactStaffingMetricsFactory::createOne([
            'dimTime' => $week1,
            'dimProfile' => $dimProfile,
            'contributor' => $contributor,
            'granularity' => 'weekly',
            'availableDays' => '5.00',
            'staffedDays' => '4.00',
        ]);

        // Act
        $results = $this->repository->getWeeklyOccupancyByContributor(2024, $profile);

        // Assert
        static::assertCount(1, $results);
    }

    public function testGetWeeklyGlobalTACE(): void
    {
        // Arrange
        $week1 = DimTimeFactory::createOne(['date' => new DateTime('2024-01-01')]);
        $dimProfile = DimProfileFactory::createOne(['isProductive' => true]);

        FactStaffingMetricsFactory::createOne([
            'dimTime' => $week1,
            'dimProfile' => $dimProfile,
            'granularity' => 'weekly',
            'staffedDays' => '17.00',
            'workedDays' => '20.00',
            'contributorCount' => 3,
        ]);
        FactStaffingMetricsFactory::createOne([
            'dimTime' => $week1,
            'dimProfile' => $dimProfile,
            'granularity' => 'weekly',
            'staffedDays' => '19.00',
            'workedDays' => '20.00',
            'contributorCount' => 2,
        ]);

        // Act
        $results = $this->repository->getWeeklyGlobalTACE(2024);

        // Assert
        static::assertCount(1, $results);
        static::assertSame('2024-S01', $results[0]['weekNumber']);
        static::assertSame(5, (int) $results[0]['contributorCount']); // 3 + 2
        // Doctrine SUM() peut renvoyer un int (PG) ou un string (MySQL). Compare en numérique.
        static::assertSame(36, (int) $results[0]['staffedDays']); // 17 + 19
        static::assertSame(40, (int) $results[0]['workedDays']); // 20 + 20
    }

    public function testGetWeeklyGlobalTACEExcludesNonProductiveProfiles(): void
    {
        // Arrange
        $week1 = DimTimeFactory::createOne(['date' => new DateTime('2024-01-01')]);

        // Productive profile
        $dimProfileProductive = DimProfileFactory::createOne(['isProductive' => true]);
        FactStaffingMetricsFactory::createOne([
            'dimTime' => $week1,
            'dimProfile' => $dimProfileProductive,
            'granularity' => 'weekly',
            'contributorCount' => 3,
        ]);

        // Non-productive profile (should be excluded)
        $dimProfileNonProductive = DimProfileFactory::createOne(['isProductive' => false]);
        FactStaffingMetricsFactory::createOne([
            'dimTime' => $week1,
            'dimProfile' => $dimProfileNonProductive,
            'granularity' => 'weekly',
            'contributorCount' => 1,
        ]);

        // No profile (should be included)
        FactStaffingMetricsFactory::createOne([
            'dimTime' => $week1,
            'dimProfile' => null,
            'granularity' => 'weekly',
            'contributorCount' => 2,
        ]);

        // Act
        $results = $this->repository->getWeeklyGlobalTACE(2024);

        // Assert
        static::assertCount(1, $results);
        // Should only count productive (3) + null profile (2) = 5
        static::assertSame(5, (int) $results[0]['contributorCount']);
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
        static::assertSame(0, $deleted);
    }
}
