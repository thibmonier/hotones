<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Message\AnalyzeProjectRisksMessage;
use App\Message\CheckAlertsMessage;
use App\Message\GenerateForecastsMessage;
use App\Message\RecalculateMetricsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Provider de schedules pour les tâches de recalcul des métriques analytics.
 */
#[AsSchedule('analytics')]
class AnalyticsScheduleProvider implements ScheduleProviderInterface
{
    public function __construct()
    {
    }

    public function getSchedule(): Schedule
    {
        $currentYear = (int) date('Y');

        return (new Schedule())
            // Recalcul quotidien des métriques mensuelles à 6h du matin
            ->add(
                RecurringMessage::cron('0 6 * * *', new RecalculateMetricsMessage((string) $currentYear, 'monthly')),
            )
            // Recalcul trimestriel le 1er de chaque trimestre à 7h
            ->add(
                RecurringMessage::cron('0 7 1 1,4,7,10 *', new RecalculateMetricsMessage((string) $currentYear, 'quarterly')),
            )
            // Recalcul annuel le 1er janvier à 8h
            ->add(
                RecurringMessage::cron('0 8 1 1 *', new RecalculateMetricsMessage((string) $currentYear, 'yearly')),
            )
            // Analyse quotidienne des risques projets à 8h (après le recalcul des métriques)
            ->add(
                RecurringMessage::cron('0 8 * * *', new AnalyzeProjectRisksMessage()),
            )
            // Vérification quotidienne des alertes à 8h (budget, marge, surcharge, paiements)
            ->add(
                RecurringMessage::cron('0 8 * * *', new CheckAlertsMessage()),
            )
            // Génération mensuelle des prévisions le 1er de chaque mois à 9h
            ->add(
                RecurringMessage::cron('0 9 1 * *', new GenerateForecastsMessage(12)),
            );
    }
}
