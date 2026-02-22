<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Entity\SchedulerEntry;
use Cron\CronExpression;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\RecurringCommand;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

class DbScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getSchedule(): Schedule
    {
        $schedule = new Schedule();

        $entries = $this->em->getRepository(SchedulerEntry::class)->findBy(['enabled' => true]);
        foreach ($entries as $entry) {
            $trigger = new CronExpressionTrigger(
                new CronExpression($entry->getCronExpression()),
                new DateTimeZone($entry->getTimezone()),
            )->withDescription($entry->getName());

            $args = $entry->getPayload() ?? [];

            $schedule->add(new RecurringCommand($trigger, $entry->getCommand(), $args)->withName($entry->getName()));
        }

        return $schedule;
    }
}
