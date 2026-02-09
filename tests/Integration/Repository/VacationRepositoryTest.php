<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Vacation;
use App\Factory\ContributorFactory;
use App\Repository\VacationRepository;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class VacationRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private VacationRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(VacationRepository::class);
        $this->setUpMultiTenant();
    }

    public function testCountApprovedDaysBetween(): void
    {
        $contributor = ContributorFactory::createOne();

        // Approved vacation fully within period (Jan 10-14 = 5 days)
        $this->createVacation($contributor, '2025-01-10', '2025-01-14', 'approved');

        // Approved vacation partially overlapping (Jan 28-Feb 3, only Jan 28-31 counted = 4 days)
        $this->createVacation($contributor, '2025-01-28', '2025-02-03', 'approved');

        // Pending vacation (excluded)
        $this->createVacation($contributor, '2025-01-20', '2025-01-22', 'pending');

        // Rejected vacation (excluded)
        $this->createVacation($contributor, '2025-01-25', '2025-01-27', 'rejected');

        $count = $this->repository->countApprovedDaysBetween(new DateTime('2025-01-01'), new DateTime('2025-01-31'));

        // 5 days (Jan 10-14) + 4 days (Jan 28-31) = 9 days
        $this->assertEquals(9.0, $count);
    }

    public function testCountApprovedDaysBetweenWithNoVacations(): void
    {
        $count = $this->repository->countApprovedDaysBetween(new DateTime('2025-01-01'), new DateTime('2025-01-31'));

        $this->assertEquals(0.0, $count);
    }

    public function testCountApprovedDaysBetweenWithVacationOutsidePeriod(): void
    {
        $contributor = ContributorFactory::createOne();

        // Vacation completely outside period
        $this->createVacation($contributor, '2024-12-01', '2024-12-15', 'approved');

        $count = $this->repository->countApprovedDaysBetween(new DateTime('2025-01-01'), new DateTime('2025-01-31'));

        $this->assertEquals(0.0, $count);
    }

    public function testFindPendingForContributors(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();
        $contributor3 = ContributorFactory::createOne();

        // Pending for contributor1
        $this->createVacation($contributor1, '2025-01-10', '2025-01-15', 'pending');

        // Pending for contributor2
        $this->createVacation($contributor2, '2025-01-20', '2025-01-25', 'pending');

        // Approved for contributor1 (excluded)
        $this->createVacation($contributor1, '2025-02-01', '2025-02-05', 'approved');

        // Pending for contributor3 (not in list, excluded)
        $this->createVacation($contributor3, '2025-03-01', '2025-03-05', 'pending');

        $results = $this->repository->findPendingForContributors([$contributor1, $contributor2]);

        $this->assertCount(2, $results);
        // Should be ordered by createdAt DESC (most recent first)
        $this->assertEquals('pending', $results[0]->getStatus());
        $this->assertEquals('pending', $results[1]->getStatus());
    }

    public function testFindPendingForContributorsWithEmptyArray(): void
    {
        $results = $this->repository->findPendingForContributors([]);

        $this->assertEmpty($results);
    }

    public function testFindPendingForContributorsWithNoPendingVacations(): void
    {
        $contributor = ContributorFactory::createOne();

        // Only approved vacations
        $this->createVacation($contributor, '2025-01-10', '2025-01-15', 'approved');

        $results = $this->repository->findPendingForContributors([$contributor]);

        $this->assertEmpty($results);
    }

    // Helper method
    private function createVacation($contributor, string $startDate, string $endDate, string $status): Vacation
    {
        $em = static::getContainer()->get('doctrine')->getManager();

        $vacation = new Vacation();
        $vacation->setCompany($this->getTestCompany());
        $vacation->setContributor($contributor);
        $vacation->setStartDate(new DateTime($startDate));
        $vacation->setEndDate(new DateTime($endDate));
        $vacation->setStatus($status);
        $vacation->setType(Vacation::TYPE_PAID_LEAVE);

        $em->persist($vacation);
        $em->flush();

        return $vacation;
    }
}
