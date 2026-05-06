<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Vacation\Query;

use App\Application\Vacation\DTO\VacationDTO;
use App\Application\Vacation\Query\GetContributorVacations\GetContributorVacationsHandler;
use App\Application\Vacation\Query\GetContributorVacations\GetContributorVacationsQuery;
use App\Domain\Vacation\Entity\Vacation;
use App\Domain\Vacation\Repository\VacationRepositoryInterface;
use App\Domain\Vacation\ValueObject\DailyHours;
use App\Domain\Vacation\ValueObject\DateRange;
use App\Domain\Vacation\ValueObject\VacationId;
use App\Domain\Vacation\ValueObject\VacationType;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GetContributorVacationsHandler (TEST-005, sprint-003).
 *
 * Covers :
 *  - Unknown contributor -> empty array (no repository call attempted on missing id)
 *  - Existing contributor -> array of VacationDTO mapped via fromEntity()
 */
#[AllowMockObjectsWithoutExpectations]
final class GetContributorVacationsHandlerTest extends TestCase
{
    private VacationRepositoryInterface&MockObject $vacationRepo;
    private ContributorRepository&MockObject $contributorRepo;
    private GetContributorVacationsHandler $handler;

    protected function setUp(): void
    {
        $this->vacationRepo = $this->createMock(VacationRepositoryInterface::class);
        $this->contributorRepo = $this->createMock(ContributorRepository::class);
        $this->handler = new GetContributorVacationsHandler($this->vacationRepo, $this->contributorRepo);
    }

    #[Test]
    public function returnsEmptyArrayWhenContributorNotFound(): void
    {
        $this->contributorRepo->method('find')->willReturn(null);
        $this->vacationRepo->expects(self::never())->method('findByContributor');

        $result = ($this->handler)(new GetContributorVacationsQuery(999_999));

        self::assertSame([], $result);
    }

    #[Test]
    public function mapsContributorVacationsToDTOs(): void
    {
        $contributor = $this->createMock(Contributor::class);
        $contributor->method('getFullName')->willReturn('Adrien Test');

        $this->contributorRepo->expects(self::once())->method('find')->with(42)->willReturn($contributor);

        $vacations = [
            $this->buildVacation('Adrien Test', VacationType::PAID_LEAVE),
            $this->buildVacation('Adrien Test', VacationType::TRAINING),
        ];
        $this->vacationRepo->expects(self::once())->method('findByContributor')->with($contributor)->willReturn($vacations);

        $result = ($this->handler)(new GetContributorVacationsQuery(42));

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(VacationDTO::class, $result);
        self::assertSame('conges_payes', $result[0]->type);
        self::assertSame('formation', $result[1]->type);
    }

    private function buildVacation(string $contributorName, VacationType $type): Vacation
    {
        $contributor = $this->createMock(Contributor::class);
        $contributor->method('getFullName')->willReturn($contributorName);

        return Vacation::request(
            VacationId::generate(),
            $this->createMock(Company::class),
            $contributor,
            DateRange::fromStrings('2026-06-01', '2026-06-03'),
            $type,
            DailyHours::fullDay(),
            'Test',
        );
    }
}
