<?php

namespace App\Event;

use App\Enum\NotificationType;

class KpiThresholdExceededEvent extends NotificationEvent
{
    public function __construct(
        private readonly string $kpiName,
        private readonly float $currentValue,
        private readonly float $thresholdValue,
        private readonly string $condition, // 'above' ou 'below'
        array $recipients
    ) {
        $title = 'Seuil KPI dépassé';

        $message = match ($condition) {
            'above' => sprintf(
                'Le KPI "%s" a dépassé le seuil : %.2f (seuil : %.2f)',
                $kpiName,
                $currentValue,
                $thresholdValue,
            ),
            'below' => sprintf(
                'Le KPI "%s" est en dessous du seuil : %.2f (seuil : %.2f)',
                $kpiName,
                $currentValue,
                $thresholdValue,
            ),
            default => sprintf(
                'Le KPI "%s" a une valeur anormale : %.2f',
                $kpiName,
                $currentValue,
            ),
        };

        parent::__construct(
            type: NotificationType::KPI_THRESHOLD_EXCEEDED,
            title: $title,
            message: $message,
            recipients: $recipients,
            data: [
                'kpi_name'        => $kpiName,
                'current_value'   => $currentValue,
                'threshold_value' => $thresholdValue,
                'condition'       => $condition,
            ],
            entityType: null,
            entityId: null,
        );
    }

    public function getKpiName(): string
    {
        return $this->kpiName;
    }

    public function getCurrentValue(): float
    {
        return $this->currentValue;
    }

    public function getThresholdValue(): float
    {
        return $this->thresholdValue;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }
}
