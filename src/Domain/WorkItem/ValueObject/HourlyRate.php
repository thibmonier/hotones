<?php

declare(strict_types=1);

namespace App\Domain\WorkItem\ValueObject;

use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;

/**
 * Hourly rate (cost or billed) — wrapper Money, **non-null par construction**.
 *
 * Mitige Risk Q3 critique audit (sprint-019) : `Contributor.cjm` / `tjm` doubles
 * nullable au niveau flat → coût/CA = 0 silencieusement → marge faussée.
 *
 * Construction côté DDD = throw si rate manquant. Forces résolution explicite
 * en amont (UC creation côté ACL Phase 2).
 *
 * Conversion CJM/TJM journaliers → HourlyRate : diviser par 8 (jour standard).
 *
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 * @see docs/02-architecture/epic-003-audit-existing-data.md
 */
final readonly class HourlyRate
{
    private const float HOURS_PER_DAY = 8.0;

    private function __construct(
        private Money $amount,
    ) {
        if (!$amount->isPositive() && !$amount->isZero()) {
            throw new InvalidArgumentException('HourlyRate amount cannot be negative');
        }
    }

    public static function fromMoney(Money $amount): self
    {
        return new self($amount);
    }

    public static function fromAmount(float $amount): self
    {
        return new self(Money::fromAmount($amount));
    }

    /**
     * Construit depuis un taux journalier (CJM ou TJM) en divisant par 8h.
     *
     * @throws InvalidArgumentException si dailyRate null ou non positif
     */
    public static function fromDailyRate(?float $dailyRate): self
    {
        if ($dailyRate === null || $dailyRate <= 0.0) {
            throw new InvalidArgumentException('HourlyRate cannot be derived from null or non-positive daily rate (Risk Q3 audit : ensure Contributor.cjm / tjm or EmploymentPeriod is set)');
        }

        return self::fromAmount($dailyRate / self::HOURS_PER_DAY);
    }

    /**
     * Construit depuis chaîne décimale Doctrine (Contributor::$cjm est string nullable).
     *
     * @throws InvalidArgumentException si valeur null, vide ou non parseable positive
     */
    public static function fromDailyRateDecimalString(?string $dailyRate): self
    {
        if ($dailyRate === null || trim($dailyRate) === '') {
            throw new InvalidArgumentException('HourlyRate cannot be derived from null/empty daily rate (Risk Q3)');
        }

        $float = (float) $dailyRate;
        if ($float <= 0.0) {
            throw new InvalidArgumentException(sprintf('HourlyRate daily rate must be > 0, got %.4f', $float));
        }

        return self::fromAmount($float / self::HOURS_PER_DAY);
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function multiply(WorkedHours $hours): Money
    {
        return $this->amount->multiply($hours->getValue());
    }

    public function equals(self $other): bool
    {
        return $this->amount->equals($other->amount);
    }
}
