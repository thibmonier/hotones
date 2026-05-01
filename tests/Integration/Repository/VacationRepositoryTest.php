<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\User;
use App\Factory\ContributorFactory;
use App\Tests\Support\MultiTenantTestTrait;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class VacationRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private VacationRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(VacationRepositoryInterface::class);
        $this->setUpMultiTenant();
    }

    public function testSaveAndFindById(): void
    {
        $contributor = ContributorFactory::createOne();
        $vacation    = $this->createVacation($contributor, '2025-01-10', '2025-01-14');

        $this->repository->save($vacation);

        $found = $this->repository->findById($vacation->getId());

        $this->assertSame($vacation->getId()->getValue(), $found->getId()->getValue());
        $this->assertSame(VacationStatus::PENDING, $found->getStatus());
    }

    public function testFindByContributor(): void
    {
        $contributor = ContributorFactory::createOne();
        $this->createAndSaveVacation($contributor, '2025-01-10', '2025-01-14');
        $this->createAndSaveVacation($contributor, '2025-02-10', '2025-02-14');

        $results = $this->repository->findByContributor($contributor);

        $this->assertCount(2, $results);
    }

    public function testCountApprovedDaysBetween(): void
    {
        $contributor = ContributorFactory::createOne();

        // Approved vacation fully within period (Jan 10-14 = 5 days)
        $this->createAndSaveVacationWithStatus($contributor, '2025-01-10', '2025-01-14', 'approved');

        // Approved vacation partially overlapping (Jan 28-Feb 3, only Jan 28-31 counted = 4 days)
        $this->createAndSaveVacationWithStatus($contributor, '2025-01-28', '2025-02-03', 'approved');

        // Pending vacation (excluded)
        $this->createAndSaveVacation($contributor, '2025-01-20', '2025-01-22');

        $count = $this->repository->countApprovedDaysBetween(
            new DateTime('2025-01-01'),
            new DateTime('2025-01-31'),
        );

        // 5 days (Jan 10-14) + 4 days (Jan 28-31) = 9 days
        $this->assertEquals(9.0, $count);
    }

    public function testCountApprovedDaysBetweenWithNoVacations(): void
    {
        $count = $this->repository->countApprovedDaysBetween(
            new DateTime('2025-01-01'),
            new DateTime('2025-01-31'),
        );

        $this->assertEquals(0.0, $count);
    }

    public function testFindPendingForContributors(): void
    {
        $contributor1 = ContributorFactory::createOne();
        $contributor2 = ContributorFactory::createOne();
        $contributor3 = ContributorFactory::createOne();

        // Pending for contributor1
        $this->createAndSaveVacation($contributor1, '2025-01-10', '2025-01-15');

        // Pending for contributor2
        $this->createAndSaveVacation($contributor2, '2025-01-20', '2025-01-25');

        // Approved for contributor1 (excluded)
        $this->createAndSaveVacationWithStatus($contributor1, '2025-02-01', '2025-02-05', 'approved');

        // Pending for contributor3 (not in list, excluded)
        $this->createAndSaveVacation($contributor3, '2025-03-01', '2025-03-05');

        $results = $this->repository->findPendingForContributors([$contributor1, $contributor2]);

        $this->assertCount(2, $results);
        $this->assertSame(VacationStatus::PENDING, $results[0]->getStatus());
        $this->assertSame(VacationStatus::PENDING, $results[1]->getStatus());
    }

    public function testFindPendingForContributorsWithEmptyArray(): void
    {
        $results = $this->repository->findPendingForContributors([]);

        $this->assertEmpty($results);
    }

    private function createVacation($contributor, string $startDate, string $endDate): Vacation
    {
        return Vacation::request(
            VacationId::generate(),
            $this->getTestCompany(),
            $contributor,
            DateRange::create(
                new DateTimeImmutable($startDate),
                new DateTimeImmutable($endDate),
            ),
            VacationType::PAID_LEAVE,
            DailyHours::fullDay(),
        );
    }

    private function createAndSaveVacation($contributor, string $startDate, string $endDate): Vacation
    {
        $vacation = $this->createVacation($contributor, $startDate, $endDate);
        $this->repository->save($vacation);

        return $vacation;
    }

    private function createAndSaveVacationWithStatus($contributor, string $startDate, string $endDate, string $status): Vacation
    {
        $vacation = $this->createVacation($contributor, $startDate, $endDate);

        if ($status === 'approved') {
            $em   = static::getContainer()->get('doctrine')->getManager();
            $user = $em->getRepository(User::class)->findOneBy([]) ?? $this->createTestUser();
            $vacation->approve($user);
        } elseif ($status === 'rejected') {
            $vacation->reject();
        } elseif ($status === 'cancelled') {
            $vacation->cancel();
        }

        $this->repository->save($vacation);

        return $vacation;
    }

    private function createTestUser(): User
    {
        $em   = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setEmail('test-vacation@example.com');
        $user->setPassword('test');
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
