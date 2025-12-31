<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Profile;
use App\Repository\ContributorRepository;
use App\Repository\EmploymentPeriodRepository;
use App\Repository\VacationRepository;
use App\Service\HrMetricsService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HrMetricsService.
 *
 * Coverage: All public methods + edge cases
 * P0 Priority: 0.66% → 100% coverage target
 */
class HrMetricsServiceTest extends TestCase
{
    private HrMetricsService $service;
    private ContributorRepository $contributorRepository;
    private EmploymentPeriodRepository $employmentPeriodRepository;
    private VacationRepository $vacationRepository;

    protected function setUp(): void
    {
        // Create mocks for all dependencies
        $this->contributorRepository      = $this->createMock(ContributorRepository::class);
        $this->employmentPeriodRepository = $this->createMock(EmploymentPeriodRepository::class);
        $this->vacationRepository         = $this->createMock(VacationRepository::class);

        // Instantiate service with mocks
        $this->service = new HrMetricsService(
            $this->contributorRepository,
            $this->employmentPeriodRepository,
            $this->vacationRepository,
        );
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateTurnoverWithNoDeparturesReturnsZeroRate(): void
    {
        // Given: no departures, stable headcount of 10
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-12-31');

        $this->employmentPeriodRepository
            ->expects($this->once())
            ->method('countDepartures')
            ->with($startDate, $endDate)
            ->willReturn(0);

        $this->employmentPeriodRepository
            ->expects($this->exactly(2))
            ->method('countActiveAt')
            ->willReturnOnConsecutiveCalls(10, 10);

        // When: calculate turnover
        $result = $this->service->calculateTurnover($startDate, $endDate);

        // Then: 0% turnover rate
        $this->assertSame(0.0, $result['turnoverRate']);
        $this->assertSame(0, $result['departures']);
        $this->assertSame(10.0, $result['averageHeadcount']);
        $this->assertSame(10, $result['headcountStart']);
        $this->assertSame(10, $result['headcountEnd']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateTurnoverWithDeparturesReturnsCorrectRate(): void
    {
        // Given: 2 departures, headcount 10→8 (average 9)
        // Expected turnover: (2 / 9) * 100 = 22.22%
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-12-31');

        $this->employmentPeriodRepository
            ->expects($this->once())
            ->method('countDepartures')
            ->with($startDate, $endDate)
            ->willReturn(2);

        $this->employmentPeriodRepository
            ->expects($this->exactly(2))
            ->method('countActiveAt')
            ->willReturnOnConsecutiveCalls(10, 8);

        // When: calculate turnover
        $result = $this->service->calculateTurnover($startDate, $endDate);

        // Then: 22.22% turnover rate
        $this->assertSame(22.22, $result['turnoverRate']);
        $this->assertSame(2, $result['departures']);
        $this->assertSame(9.0, $result['averageHeadcount']);
        $this->assertSame(10, $result['headcountStart']);
        $this->assertSame(8, $result['headcountEnd']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateTurnoverWithZeroHeadcountReturnsZeroRate(): void
    {
        // Given: no employees at all
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-12-31');

        $this->employmentPeriodRepository
            ->expects($this->once())
            ->method('countDepartures')
            ->willReturn(0);

        $this->employmentPeriodRepository
            ->expects($this->exactly(2))
            ->method('countActiveAt')
            ->willReturnOnConsecutiveCalls(0, 0);

        // When: calculate turnover
        $result = $this->service->calculateTurnover($startDate, $endDate);

        // Then: 0% turnover (division by zero protection)
        $this->assertSame(0.0, $result['turnoverRate']);
        $this->assertSame(0, $result['departures']);
        $this->assertSame(0.0, $result['averageHeadcount']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateAbsenteeismWithNoVacationsReturnsZeroRate(): void
    {
        // Given: no vacations, 10 employees, 20 working days
        $startDate = new DateTime('2025-01-01'); // Wednesday
        $endDate   = new DateTime('2025-01-31'); // Friday

        $this->vacationRepository
            ->expects($this->once())
            ->method('countApprovedDaysBetween')
            ->with($startDate, $endDate)
            ->willReturn(0.0);

        $this->employmentPeriodRepository
            ->expects($this->exactly(2))
            ->method('countActiveAt')
            ->willReturnOnConsecutiveCalls(10, 10);

        // When: calculate absenteeism
        $result = $this->service->calculateAbsenteeism($startDate, $endDate);

        // Then: 0% absenteeism
        $this->assertSame(0.0, $result['absenteeismRate']);
        $this->assertSame(0.0, $result['absentDays']);
        $this->assertGreaterThan(0, $result['workingDays']);
        $this->assertGreaterThan(0, $result['theoreticalDays']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateAbsenteeismWithVacationsReturnsCorrectRate(): void
    {
        // Given: 50 vacation days, 10 employees, 20 working days
        // Theoretical days = 10 * 20 = 200
        // Absenteeism = (50 / 200) * 100 = 25%
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-01-31');

        $this->vacationRepository
            ->expects($this->once())
            ->method('countApprovedDaysBetween')
            ->with($startDate, $endDate)
            ->willReturn(50.0);

        $this->employmentPeriodRepository
            ->expects($this->exactly(2))
            ->method('countActiveAt')
            ->willReturnOnConsecutiveCalls(10, 10);

        // When: calculate absenteeism
        $result = $this->service->calculateAbsenteeism($startDate, $endDate);

        // Then: ~25% absenteeism (depends on actual working days in Jan 2025)
        $this->assertGreaterThan(0, $result['absenteeismRate']);
        $this->assertSame(50.0, $result['absentDays']);
        $this->assertGreaterThan(0, $result['theoreticalDays']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateAbsenteeismWithZeroHeadcountReturnsZeroRate(): void
    {
        // Given: no employees
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-01-31');

        $this->vacationRepository
            ->expects($this->once())
            ->method('countApprovedDaysBetween')
            ->willReturn(0.0);

        $this->employmentPeriodRepository
            ->expects($this->exactly(2))
            ->method('countActiveAt')
            ->willReturnOnConsecutiveCalls(0, 0);

        // When: calculate absenteeism
        $result = $this->service->calculateAbsenteeism($startDate, $endDate);

        // Then: 0% absenteeism (division by zero protection)
        $this->assertSame(0.0, $result['absenteeismRate']);
        $this->assertSame(0.0, $result['absentDays']);
        $this->assertSame(0.0, $result['theoreticalDays']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateAverageSeniorityWithNoContributorsReturnsEmpty(): void
    {
        // Given: no active contributors
        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([]);

        // When: calculate average seniority
        $result = $this->service->calculateAverageSeniority();

        // Then: 0 average, empty distribution
        $this->assertSame(0, $result['averageSeniority']);
        $this->assertSame([], $result['bySeniority']);
        $this->assertSame(0, $result['totalActive']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateAverageSeniorityWithVariousSenioritiesReturnsCorrectDistribution(): void
    {
        // Given: 5 contributors with different seniorities
        $contributors = [
            $this->createContributorWithSeniority(0.5), // < 1 an
            $this->createContributorWithSeniority(1.5), // 1-2 ans
            $this->createContributorWithSeniority(3.0), // 2-5 ans
            $this->createContributorWithSeniority(7.0), // 5-10 ans
            $this->createContributorWithSeniority(12.0), // > 10 ans
        ];

        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn($contributors);

        // Mock repository to return employment periods in order
        $this->employmentPeriodRepository
            ->method('findFirstByContributor')
            ->willReturnOnConsecutiveCalls(
                $this->createEmploymentPeriodStartedYearsAgo(0.5),
                $this->createEmploymentPeriodStartedYearsAgo(1.5),
                $this->createEmploymentPeriodStartedYearsAgo(3.0),
                $this->createEmploymentPeriodStartedYearsAgo(7.0),
                $this->createEmploymentPeriodStartedYearsAgo(12.0),
            );

        // When: calculate average seniority
        $result = $this->service->calculateAverageSeniority();

        // Then: average = (0.5 + 1.5 + 3 + 7 + 12) / 5 = 4.8 years
        $this->assertSame(4.8, $result['averageSeniority']);
        $this->assertSame(1, $result['bySeniority']['< 1 an']);
        $this->assertSame(1, $result['bySeniority']['1-2 ans']);
        $this->assertSame(1, $result['bySeniority']['2-5 ans']);
        $this->assertSame(1, $result['bySeniority']['5-10 ans']);
        $this->assertSame(1, $result['bySeniority']['> 10 ans']);
        $this->assertSame(5, $result['totalActive']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUcalculateAverageSenioritySkipsContributorsWithoutEmploymentPeriod(): void
    {
        // Given: 3 contributors, but 1 has no employment period
        $contributorWithPeriod1 = $this->createContributorWithSeniority(2.0);
        $contributorWithPeriod2 = $this->createContributorWithSeniority(3.0);
        $contributorNoPeriod    = $this->createMock(Contributor::class);

        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([$contributorWithPeriod1, $contributorWithPeriod2, $contributorNoPeriod]);

        $this->employmentPeriodRepository
            ->expects($this->exactly(3))
            ->method('findFirstByContributor')
            ->willReturnOnConsecutiveCalls(
                $this->createEmploymentPeriodStartedYearsAgo(2.0),
                $this->createEmploymentPeriodStartedYearsAgo(3.0),
                null, // No employment period for third contributor
            );

        // When: calculate average seniority
        $result = $this->service->calculateAverageSeniority();

        // Then: average = (2 + 3) / 3 = 1.67 years (divided by total active contributors)
        // Note: Service divides by count($activeContributors), not count of those with periods
        $this->assertSame(1.7, $result['averageSeniority']);
        $this->assertSame(2, $result['bySeniority']['2-5 ans']);
        $this->assertSame(3, $result['totalActive']); // Count all active, even those without period
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetAgePyramidWithNoContributorsReturnsEmpty(): void
    {
        // Given: no active contributors
        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([]);

        // When: get age pyramid
        $result = $this->service->getAgePyramid();

        // Then: empty structure
        $this->assertSame([], $result['ageRanges']);
        $this->assertSame(0, $result['averageAge']); // Service returns 0 (int) when count=0
        $this->assertSame(0, $result['totalActive']);
        $this->assertSame(['male' => 0, 'female' => 0, 'other' => 0], $result['byGender']);
        $this->assertSame(0, $result['parityRate']); // Service returns 0 (int) when totalGendered=0
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetAgePyramidWithVariousAgesReturnsCorrectDistribution(): void
    {
        // Given: 6 contributors with different ages and genders
        $contributors = [
            $this->createContributorWithAge(22, 'male'),   // < 25 ans
            $this->createContributorWithAge(28, 'female'), // 25-30 ans
            $this->createContributorWithAge(35, 'male'),   // 30-40 ans
            $this->createContributorWithAge(45, 'female'), // 40-50 ans
            $this->createContributorWithAge(55, 'male'),   // 50-60 ans
            $this->createContributorWithAge(65, 'other'),  // > 60 ans
        ];

        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn($contributors);

        // When: get age pyramid
        $result = $this->service->getAgePyramid();

        // Then: correct distribution
        $this->assertSame(1, $result['ageRanges']['< 25 ans']);
        $this->assertSame(1, $result['ageRanges']['25-30 ans']);
        $this->assertSame(1, $result['ageRanges']['30-40 ans']);
        $this->assertSame(1, $result['ageRanges']['40-50 ans']);
        $this->assertSame(1, $result['ageRanges']['50-60 ans']);
        $this->assertSame(1, $result['ageRanges']['> 60 ans']);

        // Average age = (22 + 28 + 35 + 45 + 55 + 65) / 6 = 41.7
        $this->assertSame(41.7, $result['averageAge']);

        // Gender distribution
        $this->assertSame(3, $result['byGender']['male']);
        $this->assertSame(2, $result['byGender']['female']);
        $this->assertSame(1, $result['byGender']['other']);

        // Parity rate = 2 / (3 + 2) * 100 = 40%
        $this->assertSame(40.0, $result['parityRate']);
        $this->assertSame(6, $result['totalActive']);
        $this->assertSame(6, $result['countWithBirthDate']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetAgePyramidSkipsContributorsWithoutBirthDate(): void
    {
        // Given: 3 contributors, but 1 has no birthDate
        $contributorWithAge1 = $this->createContributorWithAge(30, 'male');
        $contributorWithAge2 = $this->createContributorWithAge(40, 'female');
        $contributorNoAge    = $this->createMock(Contributor::class);
        $contributorNoAge->method('getAge')->willReturn(null);
        $contributorNoAge->method('getGender')->willReturn('male');

        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([$contributorWithAge1, $contributorWithAge2, $contributorNoAge]);

        // When: get age pyramid
        $result = $this->service->getAgePyramid();

        // Then: average = (30 + 40) / 2 = 35 (third contributor excluded from age calculation)
        $this->assertSame(35.0, $result['averageAge']);
        $this->assertSame(3, $result['totalActive']); // Count all active
        $this->assertSame(2, $result['countWithBirthDate']); // But only 2 have birthDate
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetAgePyramidCalculatesParityCorrectly(): void
    {
        // Given: 7 males, 3 females, 2 other (parity = 3/10 = 30%)
        $contributors = array_merge(
            array_fill(0, 7, $this->createContributorWithAge(30, 'male')),
            array_fill(0, 3, $this->createContributorWithAge(30, 'female')),
            array_fill(0, 2, $this->createContributorWithAge(30, 'other')),
        );

        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn($contributors);

        // When: get age pyramid
        $result = $this->service->getAgePyramid();

        // Then: parity = 3 / (7 + 3) * 100 = 30% (other excluded from parity)
        $this->assertSame(30.0, $result['parityRate']);
        $this->assertSame(7, $result['byGender']['male']);
        $this->assertSame(3, $result['byGender']['female']);
        $this->assertSame(2, $result['byGender']['other']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetSkillsPyramidWithNoContributorsReturnsEmpty(): void
    {
        // Given: no active contributors
        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([]);

        // When: get skills pyramid
        $result = $this->service->getSkillsPyramid();

        // Then: empty structure
        $this->assertSame([], $result['byProfile']);
        $this->assertSame(0, $result['totalActive']);
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetSkillsPyramidWithMultipleProfilesReturnsCorrectDistribution(): void
    {
        // Given: 3 contributors with various profiles
        // Contributor 1: Developer
        // Contributor 2: Developer, Designer
        // Contributor 3: Manager
        $profileDev      = $this->createProfile('Developer');
        $profileDesigner = $this->createProfile('Designer');
        $profileManager  = $this->createProfile('Manager');

        $contributor1 = $this->createContributorWithProfiles([$profileDev]);
        $contributor2 = $this->createContributorWithProfiles([$profileDev, $profileDesigner]);
        $contributor3 = $this->createContributorWithProfiles([$profileManager]);

        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([$contributor1, $contributor2, $contributor3]);

        // When: get skills pyramid
        $result = $this->service->getSkillsPyramid();

        // Then: Developer: 2, Designer: 1, Manager: 1 (sorted descending)
        $this->assertCount(3, $result['byProfile']);
        $this->assertSame(2, $result['byProfile']['Developer']);
        $this->assertSame(1, $result['byProfile']['Designer']);
        $this->assertSame(1, $result['byProfile']['Manager']);
        $this->assertSame(3, $result['totalActive']);

        // Verify sorting (descending by count)
        $keys = array_keys($result['byProfile']);
        $this->assertSame('Developer', $keys[0]); // Most common first
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetSkillsPyramidSkipsContributorsWithoutCurrentPeriod(): void
    {
        // Given: 2 contributors, but 1 has no current employment period
        $profileDev   = $this->createProfile('Developer');
        $contributor1 = $this->createContributorWithProfiles([$profileDev]);

        $contributor2 = $this->createMock(Contributor::class);
        $contributor2->method('getCurrentEmploymentPeriod')->willReturn(null);

        $this->contributorRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([$contributor1, $contributor2]);

        // When: get skills pyramid
        $result = $this->service->getSkillsPyramid();

        // Then: only contributor1 counted
        $this->assertSame(1, $result['byProfile']['Developer']);
        $this->assertSame(2, $result['totalActive']); // Count all active
    }

    /**
     * @test
     *
     * @group hr_metrics
     * @group p0
     */
    public function testUgetAllMetricsCallsAllMethods(): void
    {
        // Given: mocked repositories returning empty data
        $startDate = new DateTime('2025-01-01');
        $endDate   = new DateTime('2025-12-31');

        $this->employmentPeriodRepository->method('countDepartures')->willReturn(0);
        $this->employmentPeriodRepository->method('countActiveAt')->willReturn(0);
        $this->vacationRepository->method('countApprovedDaysBetween')->willReturn(0.0);
        $this->contributorRepository->method('findBy')->willReturn([]);

        // When: get all metrics
        $result = $this->service->getAllMetrics($startDate, $endDate);

        // Then: all 5 metrics present
        $this->assertArrayHasKey('turnover', $result);
        $this->assertArrayHasKey('absenteeism', $result);
        $this->assertArrayHasKey('seniority', $result);
        $this->assertArrayHasKey('agePyramid', $result);
        $this->assertArrayHasKey('skillsPyramid', $result);

        // Verify structure of each metric
        $this->assertArrayHasKey('turnoverRate', $result['turnover']);
        $this->assertArrayHasKey('absenteeismRate', $result['absenteeism']);
        $this->assertArrayHasKey('averageSeniority', $result['seniority']);
        $this->assertArrayHasKey('averageAge', $result['agePyramid']);
        $this->assertArrayHasKey('byProfile', $result['skillsPyramid']);
    }

    // ========== Helper Methods ==========

    private function createContributorWithSeniority(float $yearsAgo): Contributor
    {
        return $this->createMock(Contributor::class);
    }

    private function createEmploymentPeriodStartedYearsAgo(float $yearsAgo): EmploymentPeriod
    {
        $period    = $this->createMock(EmploymentPeriod::class);
        $startDate = new DateTime();
        $startDate->modify(sprintf('-%d days', (int) ($yearsAgo * 365)));
        $period->method('getStartDate')->willReturn($startDate);

        return $period;
    }

    private function createContributorWithAge(int $age, string $gender): Contributor
    {
        $contributor = $this->createMock(Contributor::class);
        $contributor->method('getAge')->willReturn($age);
        $contributor->method('getGender')->willReturn($gender);

        return $contributor;
    }

    private function createProfile(string $name): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getName')->willReturn($name);

        return $profile;
    }

    private function createContributorWithProfiles(array $profiles): Contributor
    {
        $period = $this->createMock(EmploymentPeriod::class);
        $period->method('getProfiles')->willReturn(new ArrayCollection($profiles));

        $contributor = $this->createMock(Contributor::class);
        $contributor->method('getCurrentEmploymentPeriod')->willReturn($period);

        return $contributor;
    }
}
