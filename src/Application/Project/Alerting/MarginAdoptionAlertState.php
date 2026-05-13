<?php

declare(strict_types=1);

namespace App\Application\Project\Alerting;

use DateTimeImmutable;

/**
 * Persisted state for margin adoption red-threshold alert (US-112 T-112-04).
 *
 * Tracks consecutive days where freshPercent has been below the red
 * threshold. Persisted in `cache.kpi` pool, keyed per company.
 *
 * Used to fire Slack alert only after 7 consecutive days red (US-112 AC).
 */
final readonly class MarginAdoptionAlertState
{
    public function __construct(
        public int $consecutiveRedDays,
        public ?DateTimeImmutable $lastRedDate,
        public ?DateTimeImmutable $lastAlertSentAt,
    ) {
    }

    public static function initial(): self
    {
        return new self(consecutiveRedDays: 0, lastRedDate: null, lastAlertSentAt: null);
    }

    public function withRedToday(DateTimeImmutable $today): self
    {
        if ($this->lastRedDate === null) {
            return new self(
                consecutiveRedDays: 1,
                lastRedDate: $today,
                lastAlertSentAt: $this->lastAlertSentAt,
            );
        }

        // Consecutive : last red was yesterday (or earlier today) AND not same day
        $sameDay = $this->lastRedDate->format('Y-m-d') === $today->format('Y-m-d');
        if ($sameDay) {
            return $this; // No double-count for same day
        }

        $gap = self::daysBetween($this->lastRedDate, $today);

        if ($gap === 1) {
            return new self(
                consecutiveRedDays: $this->consecutiveRedDays + 1,
                lastRedDate: $today,
                lastAlertSentAt: $this->lastAlertSentAt,
            );
        }

        // Gap > 1 → streak broken, restart at 1
        return new self(
            consecutiveRedDays: 1,
            lastRedDate: $today,
            lastAlertSentAt: $this->lastAlertSentAt,
        );
    }

    public function withGreenToday(): self
    {
        return new self(
            consecutiveRedDays: 0,
            lastRedDate: null,
            lastAlertSentAt: $this->lastAlertSentAt,
        );
    }

    public function withAlertSentAt(DateTimeImmutable $now): self
    {
        return new self(
            consecutiveRedDays: $this->consecutiveRedDays,
            lastRedDate: $this->lastRedDate,
            lastAlertSentAt: $now,
        );
    }

    public function shouldFireAlert(int $threshold, DateTimeImmutable $now): bool
    {
        if ($this->consecutiveRedDays < $threshold) {
            return false;
        }

        // Avoid duplicate alerts < 24h apart
        if ($this->lastAlertSentAt !== null) {
            $hoursSinceLast = ($now->getTimestamp() - $this->lastAlertSentAt->getTimestamp()) / 3600;
            if ($hoursSinceLast < 24) {
                return false;
            }
        }

        return true;
    }

    private static function daysBetween(DateTimeImmutable $a, DateTimeImmutable $b): int
    {
        $aMid = $a->setTime(0, 0);
        $bMid = $b->setTime(0, 0);

        return (int) abs(($bMid->getTimestamp() - $aMid->getTimestamp()) / 86400);
    }
}
