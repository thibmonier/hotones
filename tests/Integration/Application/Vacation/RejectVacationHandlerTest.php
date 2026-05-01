<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Vacation;

use App\Application\Vacation\Command\RejectVacation\RejectVacationCommand;
use App\Application\Vacation\Command\RejectVacation\RejectVacationHandler;
use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\Contributor;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration test for RejectVacationHandler with a motif (US-068).
 *
 * Validates that the rejection_reason flows from the command through the
 * Vacation::reject() domain method down to the persisted column.
 */
final class RejectVacationHandlerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private RequestVacationHandler $requestHandler;
    private RejectVacationHandler $rejectHandler;
    private VacationRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();
        $this->requestHandler = static::getContainer()->get(RequestVacationHandler::class);
        $this->rejectHandler = static::getContainer()->get(RejectVacationHandler::class);
        $this->repository = static::getContainer()->get(VacationRepositoryInterface::class);
    }

    public function testHandlerPersistsRejectionReason(): void
    {
        $contributor = $this->createContributor();
        $vacationId = ($this->requestHandler)(new RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+2 days'),
            type: 'conges_payes',
            dailyHours: '8',
            reason: null,
        ));

        ($this->rejectHandler)(new RejectVacationCommand($vacationId->getValue(), 'Planning sature'));

        $vacation = $this->repository->findById($vacationId);
        self::assertSame(VacationStatus::REJECTED, $vacation->getStatus());
        self::assertSame('Planning sature', $vacation->getRejectionReason());
    }

    public function testHandlerKeepsNullRejectionReasonWhenOmitted(): void
    {
        $contributor = $this->createContributor();
        $vacationId = ($this->requestHandler)(new RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+2 days'),
            type: 'conges_payes',
            dailyHours: '8',
            reason: null,
        ));

        ($this->rejectHandler)(new RejectVacationCommand($vacationId->getValue()));

        $vacation = $this->repository->findById($vacationId);
        self::assertNull($vacation->getRejectionReason());
    }

    private function createContributor(): Contributor
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
