<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Vacation;

use App\Application\Vacation\Command\CancelVacation\CancelVacationCommand;
use App\Application\Vacation\Command\CancelVacation\CancelVacationHandler;
use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Company;
use App\Entity\Contributor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Unit tests for the message-bus dispatch behaviour added by TECH-DEBT-001
 * on top of the existing CancelVacationHandler.
 *
 * Two paths are exercised :
 *  - cancelledByUserId === null  -> intervenant self-cancel  -> dispatched type 'cancelled'
 *  - cancelledByUserId !== null  -> manager cancel (US-069) -> dispatched type 'cancelled-by-manager'
 *
 * The persistence side (Vacation::cancel + repository save) is already covered
 * elsewhere; this test focuses on the new notification-routing behaviour.
 */
final class CancelVacationHandlerDispatchTest extends TestCase
{
    private VacationRepositoryInterface&Stub $repository;
    private MessageBusInterface&MockObject $messageBus;
    private CancelVacationHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(VacationRepositoryInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->handler = new CancelVacationHandler($this->repository, $this->messageBus);
    }

    #[Test]
    public function selfCancelDispatchesCancelledType(): void
    {
        $vacation = $this->givenPendingVacation();
        $this->repository->method('findById')->willReturn($vacation);

        $captured = $this->expectDispatchedMessage();

        ($this->handler)(new CancelVacationCommand($vacation->getId()->getValue()));

        self::assertNotNull($captured());
        self::assertSame('cancelled', $captured()->getType());
        self::assertSame($vacation->getId()->getValue(), $captured()->getVacationId());
    }

    #[Test]
    public function managerCancelDispatchesCancelledByManagerType(): void
    {
        $vacation = $this->givenPendingVacation();
        $this->repository->method('findById')->willReturn($vacation);

        $captured = $this->expectDispatchedMessage();

        ($this->handler)(new CancelVacationCommand($vacation->getId()->getValue(), cancelledByUserId: 42));

        self::assertNotNull($captured());
        self::assertSame('cancelled-by-manager', $captured()->getType());
    }

    #[Test]
    public function commandReportsManagerInitiatedFlag(): void
    {
        self::assertFalse((new CancelVacationCommand('id', null))->isManagerInitiated());
        self::assertTrue((new CancelVacationCommand('id', 1))->isManagerInitiated());
    }

    private function givenPendingVacation(): Vacation
    {
        return Vacation::request(
            VacationId::generate(),
            $this->createStub(Company::class),
            $this->createStub(Contributor::class),
            DateRange::fromStrings('2026-06-01', '2026-06-03'),
            VacationType::PAID_LEAVE,
            DailyHours::fullDay(),
        );
    }

    /**
     * Wires expects() on the bus and returns a callable yielding the captured message.
     */
    private function expectDispatchedMessage(): callable
    {
        $captured = null;
        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$captured): Envelope {
                $captured = $message;

                return new Envelope($message);
            });

        return static function () use (&$captured): ?VacationNotificationMessage {
            return $captured instanceof VacationNotificationMessage ? $captured : null;
        };
    }
}
