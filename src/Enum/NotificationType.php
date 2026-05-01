<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Catalog of in-app notification types emitted by the application.
 *
 * Each case maps to:
 *  - a stable string `value` persisted in DB (`notifications.type` column)
 *  - a French label rendered in the UI (`getLabel()`)
 *  - a Font Awesome icon class (`getIcon()`)
 *  - a Bootstrap color (`getColor()`)
 *
 * The 10 supported types are grouped by domain:
 *
 *  COMMERCIAL (quote lifecycle)
 *    - QUOTE_TO_SIGN                : a draft quote awaits the client signature
 *    - QUOTE_WON                    : a signed quote has been won
 *    - QUOTE_LOST                   : a quote was rejected
 *
 *  PROJECT MONITORING
 *    - PROJECT_BUDGET_ALERT         : a project budget consumption is approaching the cap
 *    - LOW_MARGIN_ALERT             : the realized margin on a project drops below threshold
 *    - CONTRIBUTOR_OVERLOAD_ALERT   : a contributor planned load exceeds capacity
 *    - KPI_THRESHOLD_EXCEEDED       : a configured KPI threshold has been crossed
 *
 *  TIMESHEETS
 *    - TIMESHEET_PENDING_VALIDATION : a manager has timesheets to validate
 *    - TIMESHEET_MISSING_WEEKLY     : weekly reminder when a contributor hasn't logged time
 *
 *  BILLING
 *    - PAYMENT_DUE_ALERT            : an invoice is approaching its due date
 *
 * Adding a case: also add an arm to `getLabel()`, `getIcon()`, `getColor()`,
 * and update `tests/Unit/Enum/NotificationTypeTest.php` (`exposes_exactly_10_notification_types`).
 *
 * Recipients and event-emission rules are documented in `docs/notifications.md`.
 */
enum NotificationType: string
{
    case QUOTE_TO_SIGN                = 'quote_to_sign';
    case QUOTE_WON                    = 'quote_won';
    case QUOTE_LOST                   = 'quote_lost';
    case PROJECT_BUDGET_ALERT         = 'project_budget_alert';
    case LOW_MARGIN_ALERT             = 'low_margin_alert';
    case CONTRIBUTOR_OVERLOAD_ALERT   = 'contributor_overload_alert';
    case TIMESHEET_PENDING_VALIDATION = 'timesheet_pending_validation';
    case PAYMENT_DUE_ALERT            = 'payment_due_alert';
    case KPI_THRESHOLD_EXCEEDED       = 'kpi_threshold_exceeded';
    case TIMESHEET_MISSING_WEEKLY     = 'timesheet_missing_weekly';

    public function getLabel(): string
    {
        return match ($this) {
            self::QUOTE_TO_SIGN                => 'Nouveau devis à signer',
            self::QUOTE_WON                    => 'Devis gagné',
            self::QUOTE_LOST                   => 'Devis perdu',
            self::PROJECT_BUDGET_ALERT         => 'Budget projet proche',
            self::LOW_MARGIN_ALERT             => 'Marge faible',
            self::CONTRIBUTOR_OVERLOAD_ALERT   => 'Surcharge contributeur',
            self::TIMESHEET_PENDING_VALIDATION => 'Temps en attente de validation',
            self::PAYMENT_DUE_ALERT            => 'Échéance de paiement proche',
            self::KPI_THRESHOLD_EXCEEDED       => 'Seuil KPI dépassé',
            self::TIMESHEET_MISSING_WEEKLY     => 'Rappel saisie temps hebdo',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::QUOTE_TO_SIGN                => 'fa-file-signature',
            self::QUOTE_WON                    => 'fa-check-circle',
            self::QUOTE_LOST                   => 'fa-times-circle',
            self::PROJECT_BUDGET_ALERT         => 'fa-exclamation-triangle',
            self::LOW_MARGIN_ALERT             => 'fa-percentage',
            self::CONTRIBUTOR_OVERLOAD_ALERT   => 'fa-user-clock',
            self::TIMESHEET_PENDING_VALIDATION => 'fa-clock',
            self::PAYMENT_DUE_ALERT            => 'fa-calendar-alt',
            self::KPI_THRESHOLD_EXCEEDED       => 'fa-chart-line',
            self::TIMESHEET_MISSING_WEEKLY     => 'fa-hourglass-half',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::QUOTE_TO_SIGN                => 'info',
            self::QUOTE_WON                    => 'success',
            self::QUOTE_LOST                   => 'danger',
            self::PROJECT_BUDGET_ALERT         => 'warning',
            self::LOW_MARGIN_ALERT             => 'danger',
            self::CONTRIBUTOR_OVERLOAD_ALERT   => 'warning',
            self::TIMESHEET_PENDING_VALIDATION => 'primary',
            self::PAYMENT_DUE_ALERT            => 'warning',
            self::KPI_THRESHOLD_EXCEEDED       => 'danger',
            self::TIMESHEET_MISSING_WEEKLY     => 'primary',
        };
    }
}
