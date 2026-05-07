<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Vacation\Command;

use App\Application\Vacation\Command\ApproveVacation\ApproveVacationCommand;
use App\Application\Vacation\Command\ApproveVacation\ApproveVacationHandler;
use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
final class ApproveVacationHandlerTest extends TestCase
{
    public function testApprovePersistsAndDispatchesApprovedNotification(): void
    {
        $vacation = $this->makePendingVacation();
        $approver = $this->makeUser(99);

        $vacationRepo = $this->createMock(VacationRepositoryInterface::class);
        $vacationRepo->method('findById')->willReturn($vacation);
        $vacationRepo->expects($this->once())->method('save')->with($vacation);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getReference')->willReturn($approver);

        $captured = null;
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function ($msg) use (&$captured) {
            $captured = $msg;

            return new Envelope($msg);
        });

        $handler = new ApproveVacationHandler($vacationRepo, $em, $bus);
        $handler(new ApproveVacationCommand(
            vacationId: $vacation->getId()->getValue(),
            approvedByUserId: 99,
        ));

        $this->assertInstanceOf(VacationNotificationMessage::class, $captured);
        $this->assertSame('approved', $captured->getType());
    }

    private function makePendingVacation(): Vacation
    {
        $company = new Company();
        $contributor = new Contributor();
        $contributor->setCompany($company);
        $contributor->setFirstName('Jean');
        $contributor->setLastName('Dupont');

        return Vacation::request(
            id: VacationId::generate(),
            company: $company,
            contributor: $contributor,
            dateRange: DateRange::create(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+2 days')),
            type: VacationType::PAID_LEAVE,
            dailyHours: DailyHours::fromString('8.00'),
            reason: null,
        );
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }
}
