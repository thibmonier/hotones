<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\EmploymentPeriod;

use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\EmploymentPeriod\Repository\EmploymentPeriodRepositoryInterface;
use App\Factory\ContributorFactory;
use App\Infrastructure\EmploymentPeriod\Persistence\Doctrine\DoctrineEmploymentPeriodAdapter;
use App\Tests\Support\MultiTenantTestTrait;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Sprint-022 sub-epic E (BUFFER tests Integration sprint-021 héritage A-5
 * sprint-021 retro) — rattrapage US-100 ACL adapter Integration test.
 *
 * Vérifie que `DoctrineEmploymentPeriodAdapter` traduit correctement les
 * rows flat `EmploymentPeriod` Doctrine vers `EmploymentPeriodSnapshot`
 * Domain DTO (AT-3.1 ADR-0016 ACL pattern strangler fig).
 */
final class DoctrineEmploymentPeriodAdapterTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;
    use MultiTenantTestTrait;

    private DoctrineEmploymentPeriodAdapter $adapter;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->adapter = static::getContainer()->get(EmploymentPeriodRepositoryInterface::class);
        $this->setUpMultiTenant();
    }

    public function testFindActiveSnapshotReturnsValuesFromActivePeriod(): void
    {
        $contributor = ContributorFactory::createOne();
        $contributorId = $contributor->getId();
        self::assertNotNull($contributorId);

        $snapshot = $this->adapter->findActiveSnapshotForContributor(
            ContributorId::fromLegacyInt($contributorId),
            new DateTimeImmutable('today'),
        );

        // ContributorFactory crée auto un EmploymentPeriod avec weeklyHours 35.0
        self::assertNotNull($snapshot);
        self::assertSame(35.0, $snapshot->weeklyHours->getValue());
    }

    public function testFindActiveSnapshotReturnsNullForUuidId(): void
    {
        // Adapter ne supporte que ContributorId legacy (sprint-020 #207
        // strangler fig pattern). Non-legacy → null.
        $snapshot = $this->adapter->findActiveSnapshotForContributor(
            ContributorId::generate(),
            new DateTimeImmutable('today'),
        );

        self::assertNull($snapshot);
    }

    public function testDailyMaxHoursComputedFromSnapshot(): void
    {
        $contributor = ContributorFactory::createOne();
        $contributorId = $contributor->getId();
        self::assertNotNull($contributorId);

        $snapshot = $this->adapter->findActiveSnapshotForContributor(
            ContributorId::fromLegacyInt($contributorId),
            new DateTimeImmutable('today'),
        );

        self::assertNotNull($snapshot);
        // 35h × 100% / 5 = 7h
        self::assertSame(7.0, $snapshot->dailyMaxHours()->getValue());
    }
}
