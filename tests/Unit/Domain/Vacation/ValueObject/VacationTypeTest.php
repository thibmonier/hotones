<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Vacation\ValueObject;

use App\Domain\Vacation\ValueObject\VacationType;
use PHPUnit\Framework\TestCase;

/**
 * TEST-COVERAGE-010 (sprint-020) — coverage Vacation\VacationType.
 */
final class VacationTypeTest extends TestCase
{
    public function testAllCasesHaveLabel(): void
    {
        foreach (VacationType::cases() as $case) {
            self::assertNotEmpty($case->label());
        }
    }

    public function testPaidLeaveLabel(): void
    {
        self::assertSame('Conges payes', VacationType::PAID_LEAVE->label());
    }

    public function testCompensatoryRestLabel(): void
    {
        self::assertSame('Repos compensateur', VacationType::COMPENSATORY_REST->label());
    }

    public function testExceptionalAbsenceLabel(): void
    {
        self::assertSame('Absence exceptionnelle', VacationType::EXCEPTIONAL_ABSENCE->label());
    }

    public function testSickLeaveLabel(): void
    {
        self::assertSame('Arret maladie', VacationType::SICK_LEAVE->label());
    }

    public function testTrainingLabel(): void
    {
        self::assertSame('Formation', VacationType::TRAINING->label());
    }

    public function testOtherLabel(): void
    {
        self::assertSame('Autre', VacationType::OTHER->label());
    }

    public function testChoicesReturnsLabelToValueMapping(): void
    {
        $choices = VacationType::choices();

        self::assertCount(6, $choices);
        self::assertArrayHasKey('Conges payes', $choices);
        self::assertSame('conges_payes', $choices['Conges payes']);
        self::assertArrayHasKey('Autre', $choices);
        self::assertSame('autre', $choices['Autre']);
    }

    public function testChoicesContainsAllCases(): void
    {
        $choices = VacationType::choices();
        self::assertCount(count(VacationType::cases()), $choices);
    }

    public function testEnumValueStability(): void
    {
        // Critical : values stockées en DB. Casser les values = migration data.
        self::assertSame('conges_payes', VacationType::PAID_LEAVE->value);
        self::assertSame('repos_compensateur', VacationType::COMPENSATORY_REST->value);
        self::assertSame('absence_exceptionnelle', VacationType::EXCEPTIONAL_ABSENCE->value);
        self::assertSame('arret_maladie', VacationType::SICK_LEAVE->value);
        self::assertSame('formation', VacationType::TRAINING->value);
        self::assertSame('autre', VacationType::OTHER->value);
    }
}
