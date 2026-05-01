<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Vacation;

use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Contributor;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration test for RequestVacationHandler (US-066, T-066-06).
 *
 * Boots the full container so the real VacationRepository (Doctrine), the real
 * ContributorRepository and the real MessageBus are wired. Validates the
 * persistence side of the request flow + the rejection path when the
 * contributor cannot be resolved.
 */
final class RequestVacationHandlerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private RequestVacationHandler $handler;
    private VacationRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();
        $this->handler = static::getContainer()->get(RequestVacationHandler::class);
        $this->repository = static::getContainer()->get(VacationRepositoryInterface::class);
    }

    public function testHandlerPersistsVacationInPendingStatus(): void
    {
        $contributor = $this->createTestContributor();

        $command = new RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+3 days'),
            type: VacationType::PAID_LEAVE->value,
            dailyHours: '8',
            reason: 'Family event',
        );

        $vacationId = ($this->handler)($command);

        $vacation = $this->repository->findById($vacationId);
        self::assertSame(VacationStatus::PENDING, $vacation->getStatus());
        self::assertSame($contributor->getId(), $vacation->getContributor()->getId());
        self::assertSame(VacationType::PAID_LEAVE, $vacation->getType());
        self::assertSame('8', $vacation->getDailyHours()->getValue());
        self::assertSame('Family event', $vacation->getReason());
    }

    public function testHandlerThrowsWhenContributorIsUnknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contributor not found');

        $command = new RequestVacationCommand(
            contributorId: 999_999,
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+2 days'),
            type: VacationType::PAID_LEAVE->value,
            dailyHours: '8',
            reason: null,
        );

        ($this->handler)($command);
    }

    private function createTestContributor(): Contributor
    {
        $em = $this->getEntityManager();
        $contributor = new Contributor();
        $contributor->setCompany($this->getTestCompany());
        $contributor->setUser($this->testUser);
        $contributor->setFirstName('Adrien');
        $contributor->setLastName('Test');
        $contributor->setActive(true);

        $em->persist($contributor);
        $em->flush();

        return $contributor;
    }
}
