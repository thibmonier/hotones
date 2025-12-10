<?php

namespace App\Enum;

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
