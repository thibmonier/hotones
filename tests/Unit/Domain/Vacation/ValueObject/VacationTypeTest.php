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
            static::assertNotEmpty($case->label());
        }
    }

    public function testPaidLeaveLabel(): void
    {
        static::assertSame('Conges payes', VacationType::PAID_LEAVE->label());
    }

    public function testCompensatoryRestLabel(): void
    {
        static::assertSame('Repos compensateur', VacationType::COMPENSATORY_REST->label());
    }

    public function testExceptionalAbsenceLabel(): void
    {
        static::assertSame('Absence exceptionnelle', VacationType::EXCEPTIONAL_ABSENCE->label());
    }

    public function testSickLeaveLabel(): void
    {
        static::assertSame('Arret maladie', VacationType::SICK_LEAVE->label());
    }

    public function testTrainingLabel(): void
    {
        static::assertSame('Formation', VacationType::TRAINING->label());
    }

    public function testOtherLabel(): void
    {
        static::assertSame('Autre', VacationType::OTHER->label());
    }

    public function testChoicesReturnsLabelToValueMapping(): void
    {
        $choices = VacationType::choices();

        static::assertCount(6, $choices);
        static::assertArrayHasKey('Conges payes', $choices);
        static::assertSame('conges_payes', $choices['Conges payes']);
        static::assertArrayHasKey('Autre', $choices);
        static::assertSame('autre', $choices['Autre']);
    }

    public function testChoicesContainsAllCases(): void
    {
        $choices = VacationType::choices();
        static::assertCount(count(VacationType::cases()), $choices);
    }

    public function testEnumValueStability(): void
    {
        // Critical : values stockées en DB. Casser les values = migration data.
        static::assertSame('conges_payes', VacationType::PAID_LEAVE->value);
        static::assertSame('repos_compensateur', VacationType::COMPENSATORY_REST->value);
        static::assertSame('absence_exceptionnelle', VacationType::EXCEPTIONAL_ABSENCE->value);
        static::assertSame('arret_maladie', VacationType::SICK_LEAVE->value);
        static::assertSame('formation', VacationType::TRAINING->value);
        static::assertSame('autre', VacationType::OTHER->value);
    }
}
