<?php

declare(strict_types=1);

namespace App\Tests\Integration\Application\Vacation;

use App\Application\Vacation\Command\ApproveVacation\ApproveVacationCommand;
use App\Application\Vacation\Command\ApproveVacation\ApproveVacationHandler;
use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Domain\Vacation\Exception\InvalidStatusTransitionException;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\VacationStatus;
use App\Entity\Contributor;
use App\Entity\User;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration test for ApproveVacationHandler (US-067).
 *
 * Boots the full container so the real Vacation aggregate is loaded from the
 * Doctrine repository and the manager User reference is resolved through the
 * EntityManager. Validates the happy path (PENDING -> APPROVED) and the
 * rejection of an already-approved vacation (PENDING is the only valid source).
 */
final class ApproveVacationHandlerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private RequestVacationHandler $requestHandler;
    private ApproveVacationHandler $approveHandler;
    private VacationRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();
        $this->requestHandler = static::getContainer()->get(RequestVacationHandler::class);
        $this->approveHandler = static::getContainer()->get(ApproveVacationHandler::class);
        $this->repository = static::getContainer()->get(VacationRepositoryInterface::class);
    }

    public function testHandlerApprovesPendingVacation(): void
    {
        [$contributor, $manager] = $this->provisionTeam();
        $vacationId = $this->submitVacation($contributor);

        ($this->approveHandler)(new ApproveVacationCommand($vacationId->getValue(), $manager->getId()));

        $vacation = $this->repository->findById($vacationId);
        static::assertSame(VacationStatus::APPROVED, $vacation->getStatus());
    }

    public function testHandlerRejectsApprovalOfAlreadyApprovedVacation(): void
    {
        [$contributor, $manager] = $this->provisionTeam();
        $vacationId = $this->submitVacation($contributor);

        ($this->approveHandler)(new ApproveVacationCommand($vacationId->getValue(), $manager->getId()));

        $this->expectException(InvalidStatusTransitionException::class);

        ($this->approveHandler)(new ApproveVacationCommand($vacationId->getValue(), $manager->getId()));
    }

    /**
     * @return array{0: Contributor, 1: User}
     */
    private function provisionTeam(): array
    {
        $em = $this->getEntityManager();

        $managerUser = new User();
        $managerUser->setCompany($this->getTestCompany());
        $managerUser->setEmail('manager-handler@test.com');
        $managerUser->setPassword('password');
        $managerUser->firstName = 'Manon';
        $managerUser->lastName = 'Boss';
        $managerUser->setRoles(['ROLE_MANAGER']);
        $em->persist($managerUser);

        $contributor = new Contributor();
        $contributor->setCompany($this->getTestCompany());
        $contributor->setUser($this->testUser);
        $contributor->setFirstName('Adrien');
        $contributor->setLastName('Test');
        $contributor->setActive(true);
        $em->persist($contributor);

        $em->flush();

        return [$contributor, $managerUser];
    }

    private function submitVacation(Contributor $contributor): \App\Domain\Vacation\ValueObject\VacationId
    {
        return ($this->requestHandler)(new RequestVacationCommand(
            contributorId: $contributor->getId(),
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+3 days'),
            type: 'conges_payes',
            dailyHours: '8',
            reason: 'Test approve flow',
        ));
    }
}
