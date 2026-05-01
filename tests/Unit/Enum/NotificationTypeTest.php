<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\NotificationType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NotificationType enum.
 *
 * Documents the 10 supported notification types and verifies that
 * label / icon / color match() arms remain exhaustive — adding a new
 * case without updating the three accessors would crash here.
 */
final class NotificationTypeTest extends TestCase
{
    #[Test]
    public function exposes_exactly_10_notification_types(): void
    {
        self::assertCount(10, NotificationType::cases(), 'NotificationType enum must expose 10 cases — update tests if a case is added/removed.');
    }

    /**
     * @return iterable<string, array{NotificationType, string}>
     */
    public static function casesWithExpectedValueProvider(): iterable
    {
        yield 'quote_to_sign'                => [NotificationType::QUOTE_TO_SIGN, 'quote_to_sign'];
        yield 'quote_won'                    => [NotificationType::QUOTE_WON, 'quote_won'];
        yield 'quote_lost'                   => [NotificationType::QUOTE_LOST, 'quote_lost'];
        yield 'project_budget_alert'         => [NotificationType::PROJECT_BUDGET_ALERT, 'project_budget_alert'];
        yield 'low_margin_alert'             => [NotificationType::LOW_MARGIN_ALERT, 'low_margin_alert'];
        yield 'contributor_overload_alert'   => [NotificationType::CONTRIBUTOR_OVERLOAD_ALERT, 'contributor_overload_alert'];
        yield 'timesheet_pending_validation' => [NotificationType::TIMESHEET_PENDING_VALIDATION, 'timesheet_pending_validation'];
        yield 'payment_due_alert'            => [NotificationType::PAYMENT_DUE_ALERT, 'payment_due_alert'];
        yield 'kpi_threshold_exceeded'       => [NotificationType::KPI_THRESHOLD_EXCEEDED, 'kpi_threshold_exceeded'];
        yield 'timesheet_missing_weekly'     => [NotificationType::TIMESHEET_MISSING_WEEKLY, 'timesheet_missing_weekly'];
    }

    #[Test]
    #[DataProvider('casesWithExpectedValueProvider')]
    public function string_value_matches_expected(NotificationType $case, string $expectedValue): void
    {
        self::assertSame($expectedValue, $case->value);
    }

    #[Test]
    public function getLabel_returns_non_empty_french_label_for_all_cases(): void
    {
        foreach (NotificationType::cases() as $case) {
            $label = $case->getLabel();
            self::assertNotEmpty($label, sprintf('Empty label for case %s', $case->name));
            // Each label is a French sentence — should not equal the raw enum value.
            self::assertNotSame($case->value, $label, sprintf('Label collides with raw value for %s', $case->name));
        }
    }

    #[Test]
    public function getIcon_returns_fontawesome_class_for_all_cases(): void
    {
        foreach (NotificationType::cases() as $case) {
            $icon = $case->getIcon();
            self::assertStringStartsWith('fa-', $icon, sprintf('Icon for %s must start with fa-', $case->name));
        }
    }

    #[Test]
    public function getColor_returns_known_bootstrap_color_for_all_cases(): void
    {
        $allowedColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

        foreach (NotificationType::cases() as $case) {
            self::assertContains($case->getColor(), $allowedColors, sprintf('Unexpected color "%s" for %s', $case->getColor(), $case->name));
        }
    }

    #[Test]
    public function from_round_trips_for_all_string_values(): void
    {
        foreach (NotificationType::cases() as $case) {
            self::assertSame($case, NotificationType::from($case->value));
        }
    }

    #[Test]
    public function tryFrom_returns_null_for_unknown_value(): void
    {
        self::assertNull(NotificationType::tryFrom('unknown_event_type'));
    }
}
