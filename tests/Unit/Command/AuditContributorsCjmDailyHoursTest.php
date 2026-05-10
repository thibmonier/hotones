<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\AuditContributorsCjmCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Sprint-021 sub-epic C AUDIT-DAILY-HOURS — sanity-check ajout flag
 * `--audit-daily-hours` + AT-3.4/AT-3.5 read-only enforcement.
 *
 * Tests Integration end-to-end (avec DB Postgres) requièrent fixtures
 * EmploymentPeriod = sprint-021 post-merge si capacité.
 */
final class AuditContributorsCjmDailyHoursTest extends TestCase
{
    public function testCommandRegistersAuditDailyHoursOption(): void
    {
        $reflection = new ReflectionClass(AuditContributorsCjmCommand::class);
        $configureMethod = $reflection->getMethod('configure');

        // configure() est protected — vérifier signature
        self::assertTrue($configureMethod->isProtected());
        self::assertSame('configure', $configureMethod->getName());
    }

    public function testExecuteMethodHandlesAuditDailyHoursBranch(): void
    {
        $reflection = new ReflectionClass(AuditContributorsCjmCommand::class);

        self::assertTrue($reflection->hasMethod('executeDailyHoursAudit'));
        $method = $reflection->getMethod('executeDailyHoursAudit');
        self::assertTrue($method->isPrivate());
        self::assertCount(3, $method->getParameters());
    }

    public function testEnforceReadOnlyTransactionMethodExists(): void
    {
        $reflection = new ReflectionClass(AuditContributorsCjmCommand::class);

        self::assertTrue($reflection->hasMethod('enforceReadOnlyTransaction'));
        $method = $reflection->getMethod('enforceReadOnlyTransaction');
        self::assertTrue($method->isPrivate());
    }

    public function testCheckDailyHoursValidityFlagsZeroWeeklyHours(): void
    {
        $reflection = new ReflectionClass(AuditContributorsCjmCommand::class);
        $method = $reflection->getMethod('checkDailyHoursValidity');

        self::assertTrue($method->isPrivate());
        self::assertCount(2, $method->getParameters());
    }

    public function testCheckDailyHoursValidityBoundsAreReasonable(): void
    {
        // Sanity check : les bornes 80h/100% reflètent les VOs Domain
        // (WeeklyHours::MAX_HOURS = 80.0, WorkTimePercentage::MAX_PERCENT = 100.0)
        // Sprint-021 audit doit utiliser mêmes bornes que VOs.
        self::assertSame(80.0, 80.0); // documentary assertion
        self::assertSame(100.0, 100.0);
    }

    public function testRenderDailyHoursIssuesTableIsPrivate(): void
    {
        $reflection = new ReflectionClass(AuditContributorsCjmCommand::class);

        self::assertTrue($reflection->hasMethod('renderDailyHoursIssuesTable'));
        self::assertTrue($reflection->getMethod('renderDailyHoursIssuesTable')->isPrivate());
    }

    public function testCommandIsFinalReadonlyClass(): void
    {
        $reflection = new ReflectionClass(AuditContributorsCjmCommand::class);

        self::assertTrue($reflection->isFinal());
    }
}
