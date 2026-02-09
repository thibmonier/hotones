<?php

declare(strict_types=1);

namespace App\Scheduler;

use DateTimeZone;
use Symfony\Component\Scheduler\RecurringCommand;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

class NotificationsScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        // Rappel de saisie des temps hebdo: vendredi 12:00 (Europe/Paris)
        $trigger = new CronExpressionTrigger('0 12 * * 5', new DateTimeZone('Europe/Paris'))->withDescription(
            'Rappel hebdomadaire de saisie des temps',
        );

        $schedule->add(new RecurringCommand($trigger, 'app:notify:timesheets-weekly')->withName(
            'notifications:timesheets-weekly',
        ));

        return $schedule;
    }
}
