<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Vacation\Query;

use App\Application\Vacation\DTO\VacationDTO;
use App\Application\Vacation\Query\GetPendingVacationsForManager\GetPendingVacationsForManagerHandler;
use App\Application\Vacation\Query\GetPendingVacationsForManager\GetPendingVacationsForManagerQuery;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GetPendingVacationsForManagerHandler (TEST-005, sprint-003).
 *
 * Covers :
 *  - Manager not found in contributor table -> empty
 *  - Manager found -> repository queried with the managed contributors collection
 *    and result mapped to VacationDTO[]
 */
#[AllowMockObjectsWithoutExpectations]
final class GetPendingVacationsForManagerHandlerTest extends TestCase
{
    private VacationRepositoryInterface&MockObject $vacationRepo;
    private ContributorRepository&MockObject $contributorRepo;
    private GetPendingVacationsForManagerHandler $handler;

    protected function setUp(): void
    {
        $this->vacationRepo = $this->createMock(VacationRepositoryInterface::class);
        $this->contributorRepo = $this->createMock(ContributorRepository::class);
        $this->handler = new GetPendingVacationsForManagerHandler($this->vacationRepo, $this->contributorRepo);
    }

    #[Test]
    public function returnsEmptyArrayWhenManagerNotResolved(): void
    {
        $this->contributorRepo->method('findOneBy')->willReturn(null);
        $this->vacationRepo->expects(self::never())->method('findPendingForContributors');

        $result = ($this->handler)(new GetPendingVacationsForManagerQuery(7));

        self::assertSame([], $result);
    }

    #[Test]
    public function mapsPendingVacationsToDTOs(): void
    {
        $manager = $this->createStub(Contributor::class);
        $teamMate = $this->createStub(Contributor::class);
        $teamMate->method('getFullName')->willReturn('Adrien Test');

        $managed = new ArrayCollection([$teamMate]);
        $manager->method('getManagedContributors')->willReturn($managed);

        $this->contributorRepo->expects(self::once())->method('findOneBy')->with(['user' => 7])->willReturn($manager);

        $vacations = [$this->buildVacation('Adrien Test', VacationType::PAID_LEAVE)];
        $this->vacationRepo
            ->expects(self::once())
            ->method('findPendingForContributors')
            ->with([$teamMate])
            ->willReturn($vacations);

        $result = ($this->handler)(new GetPendingVacationsForManagerQuery(7));

        self::assertCount(1, $result);
        self::assertInstanceOf(VacationDTO::class, $result[0]);
        self::assertSame('Adrien Test', $result[0]->contributorName);
        self::assertSame('conges_payes', $result[0]->type);
    }

    private function buildVacation(string $contributorName, VacationType $type): Vacation
    {
        $contributor = $this->createStub(Contributor::class);
        $contributor->method('getFullName')->willReturn($contributorName);

        return Vacation::request(
            VacationId::generate(),
            $this->createStub(Company::class),
            $contributor,
            DateRange::fromStrings('2026-06-15', '2026-06-19'),
            $type,
            DailyHours::fullDay(),
            'Pending',
        );
    }
}
