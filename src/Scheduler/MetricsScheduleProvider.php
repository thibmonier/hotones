<?php

namespace App\Scheduler;

use DateTimeZone;
use Symfony\Component\Scheduler\RecurringCommand;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

class MetricsScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        // Recalcul KPI quotidien à 02:30
        $trigger = new CronExpressionTrigger('30 2 * * *', new DateTimeZone('Europe/Paris'))
            ->withDescription('Recalcul quotidien des métriques');

        $schedule->add(
            new RecurringCommand($trigger, 'app:metrics:dispatch', ['--date' => 'today', '--granularity' => 'daily'])
                ->withName('metrics:daily'),
        );

        return $schedule;
    }
}
