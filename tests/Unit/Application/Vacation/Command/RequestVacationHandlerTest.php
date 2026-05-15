<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Vacation\Command;

use App\Application\Vacation\Command\RequestVacation\RequestVacationCommand;
use App\Application\Vacation\Command\RequestVacation\RequestVacationHandler;
use App\Application\Vacation\Notification\Message\VacationNotificationMessage;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
final class RequestVacationHandlerTest extends TestCase
{
    public function testRequestVacationPersistsAndDispatchesNotification(): void
    {
        $contributor = $this->makeContributor();

        $contributorRepo = $this->createMock(ContributorRepository::class);
        $contributorRepo->method('find')->willReturn($contributor);

        $vacationRepo = $this->createMock(VacationRepositoryInterface::class);
        $vacationRepo->expects($this->once())
            ->method('save')
            ->with(static::isInstanceOf(Vacation::class));

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with(static::isInstanceOf(VacationNotificationMessage::class))
            ->willReturn(new Envelope(new stdClass()));

        $handler = new RequestVacationHandler($vacationRepo, $contributorRepo, $bus);

        $vacationId = $handler(new RequestVacationCommand(
            contributorId: 7,
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+3 days'),
            type: 'conges_payes',
            dailyHours: '8.00',
            reason: 'Vacation reason',
        ));

        static::assertNotEmpty($vacationId->getValue());
    }

    public function testThrowsWhenContributorNotFound(): void
    {
        $contributorRepo = $this->createMock(ContributorRepository::class);
        $contributorRepo->method('find')->willReturn(null);

        $vacationRepo = $this->createMock(VacationRepositoryInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $handler = new RequestVacationHandler($vacationRepo, $contributorRepo, $bus);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Contributor not found');

        $handler(new RequestVacationCommand(
            contributorId: 999,
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+3 days'),
            type: 'conges_payes',
        ));
    }

    public function testNotificationMessageHasCreatedAction(): void
    {
        $contributor = $this->makeContributor();

        $contributorRepo = $this->createMock(ContributorRepository::class);
        $contributorRepo->method('find')->willReturn($contributor);

        $vacationRepo = $this->createMock(VacationRepositoryInterface::class);

        $captured = null;
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(static function ($msg) use (&$captured) {
            $captured = $msg;

            return new Envelope($msg);
        });

        $handler = new RequestVacationHandler($vacationRepo, $contributorRepo, $bus);
        $handler(new RequestVacationCommand(
            contributorId: 1,
            startDate: new DateTimeImmutable('+1 day'),
            endDate: new DateTimeImmutable('+1 day'),
            type: 'conges_payes',
        ));

        static::assertInstanceOf(VacationNotificationMessage::class, $captured);
        static::assertSame('created', $captured->getType());
    }

    private function makeContributor(): Contributor
    {
        $company = new Company();
        $contributor = new Contributor();
        $contributor->setCompany($company);
        $contributor->setFirstName('Jean');
        $contributor->setLastName('Dupont');

        return $contributor;
    }
}
