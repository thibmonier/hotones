<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Worked hours value object — décimal positif strict, max 24h/jour.
 *
 * Mitige Risk Q5 audit (sprint-019) : pas de validation `hours > 24` ni
 * négatives au niveau Timesheet flat.
 *
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 * @see docs/02-architecture/epic-003-audit-existing-data.md
 */
final readonly class WorkedHours implements Stringable
{
    private const float MAX_HOURS_PER_DAY = 24.0;
    private const int DECIMAL_PRECISION = 2;

    private function __construct(
        private float $value,
    ) {
        if ($value <= 0.0) {
            throw new InvalidArgumentException(sprintf('WorkedHours must be strictly positive, got %.2f', $value));
        }

        if ($value > self::MAX_HOURS_PER_DAY) {
            throw new InvalidArgumentException(sprintf('WorkedHours cannot exceed %.0f hours per day, got %.2f', self::MAX_HOURS_PER_DAY, $value));
        }
    }

    public static function fromFloat(float $value): self
    {
        return new self(round($value, self::DECIMAL_PRECISION));
    }

    /**
     * Construit depuis la chaîne décimale Doctrine (`Timesheet::$hours` est decimal(5,2)).
     */
    public static function fromDecimalString(string $value): self
    {
        return self::fromFloat((float) $value);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return abs($this->value - $other->value) < 0.001;
    }

    public function __toString(): string
    {
        return number_format($this->value, self::DECIMAL_PRECISION, '.', '');
    }
}
