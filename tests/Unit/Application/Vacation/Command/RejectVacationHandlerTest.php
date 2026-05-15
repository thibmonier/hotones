<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Vacation\Command;

use App\Application\Vacation\Command\RejectVacation\RejectVacationCommand;
use App\Application\Vacation\Command\RejectVacation\RejectVacationHandler;
use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Company;
use App\Entity\Contributor;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
final class RejectVacationHandlerTest extends TestCase
{
    public function testRejectPersistsAndDispatchesRejectedNotification(): void
    {
        $vacation = $this->makePendingVacation();

        $vacationRepo = $this->createMock(VacationRepositoryInterface::class);
        $vacationRepo->method('findById')->willReturn($vacation);
        $vacationRepo->expects($this->once())->method('save')->with($vacation);

        $captured = null;
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(static function ($msg) use (&$captured) {
            $captured = $msg;

            return new Envelope($msg);
        });

        $handler = new RejectVacationHandler($vacationRepo, $bus);
        $handler(new RejectVacationCommand(
            vacationId: $vacation->getId()->getValue(),
            rejectionReason: 'Equipe saturee',
        ));

        static::assertInstanceOf(VacationNotificationMessage::class, $captured);
        static::assertSame('rejected', $captured->getType());
    }

    public function testRejectWithoutReason(): void
    {
        $vacation = $this->makePendingVacation();

        $vacationRepo = $this->createMock(VacationRepositoryInterface::class);
        $vacationRepo->method('findById')->willReturn($vacation);
        $vacationRepo->expects($this->once())->method('save');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $handler = new RejectVacationHandler($vacationRepo, $bus);
        $handler(new RejectVacationCommand(
            vacationId: $vacation->getId()->getValue(),
            rejectionReason: null,
        ));

        // No exception thrown — handler completed, vacation rejected without reason
        static::assertNull($vacation->getRejectionReason());
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
}
